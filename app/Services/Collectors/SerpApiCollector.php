<?php

namespace App\Services\Collectors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SerpApiCollector implements ProductCollectorInterface
{
    private const API_URL = 'https://serpapi.com/search.json';

    public function search(string $query, array $filters = []): array
    {
        $apiKey = config('services.serpapi.key');

        if (! $apiKey) {
            throw new RuntimeException(
                'A variável SERPAPI_KEY não foi configurada.'
            );
        }

        $response = Http::acceptJson()
            ->timeout(45)
            ->retry(2, 500)
            ->get(self::API_URL, [
                'engine' => 'google_shopping',
                'q' => $this->buildQuery($query, $filters),
                'gl' => 'br',
                'hl' => 'pt-br',
                'api_key' => $apiKey,
                'num' => 20,
            ]);

        if ($response->status() === 401) {
            throw new RuntimeException(
                'A chave da SerpApi é inválida.'
            );
        }

        if ($response->status() === 429) {
            throw new RuntimeException(
                'O limite de pesquisas da SerpApi foi atingido.'
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Erro da SerpApi: ' . $response->body()
            );
        }

        $error = $response->json('error');

        if (is_string($error) && $error !== '') {
            throw new RuntimeException(
                'Erro da SerpApi: ' . $error
            );
        }

        $results = $response->json('shopping_results', []);

        if (! is_array($results)) {
            return [];
        }

        return collect($results)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(
                fn (array $item): array =>
                    $this->normalizeItem($item, $filters)
            )
            ->filter(
                fn (array $item): bool =>
                    $this->isValidItem($item, $filters)
            )
            ->take(10)
            ->values()
            ->all();
    }

    private function buildQuery(
        string $originalQuery,
        array $filters
    ): string {
        $parts = [];

        if (! empty($filters['produto'])) {
            $parts[] = $filters['produto'];
        }

        if (! empty($filters['marca'])) {
            $parts[] = $filters['marca'];
        }

        if (! empty($filters['modelo'])) {
            $parts[] = $filters['modelo'];
        }

        if (! empty($filters['processador'])) {
            $parts[] = $filters['processador'];
        }

        if (! empty($filters['ram_gb'])) {
            $parts[] = $filters['ram_gb'] . 'GB RAM';
        }

        if (! empty($filters['storage_gb'])) {
            $parts[] = $filters['storage_gb'] . 'GB SSD';
        }

        if (($filters['condicao'] ?? null) === 'novo') {
            $parts[] = 'novo';
        }

        return $parts !== []
            ? implode(' ', array_unique($parts))
            : trim($originalQuery);
    }

    private function normalizeItem(
        array $item,
        array $filters
    ): array {
        $title = trim((string) ($item['title'] ?? ''));

        $url = $this->normalizeUrl(
            $item['link']
                ?? $item['product_link']
                ?? null
        );

        $price = $item['extracted_price']
            ?? $this->extractPrice($item['price'] ?? null);

        $store = trim((string) (
            $item['source']
                ?? $item['merchant']
                ?? 'Loja não informada'
        ));

        $delivery = isset($item['delivery'])
            ? (string) $item['delivery']
            : null;

        $freeShipping = is_string($delivery) &&
            Str::contains(
                Str::lower($delivery),
                [
                    'grátis',
                    'gratis',
                    'frete grátis',
                    'frete gratis',
                    'free delivery',
                    'free shipping',
                ]
            );

        $productId = $item['product_id']
            ?? sha1(($url ?? '') . $title . $store);

        return [
            'external_identifier' => 'SERPAPI-' . $productId,

            'catalog_product_id' => $item['product_id'] ?? null,

            'title' => $title,

            'brand' => $this->resolveBrand(
                $title,
                $filters['marca'] ?? null
            ),

            'model' => $filters['modelo'] ?? null,
            'gtin' => null,

            'price' => is_numeric($price)
                ? (float) $price
                : null,

            'shipping_price' => $freeShipping
                ? 0.0
                : null,

            'free_shipping' => $freeShipping,

            'installment_price' => null,
            'installments_quantity' => null,

            'seller' => $store,
            'store' => $store,
            'url' => $url,

            'image' => $item['thumbnail'] ?? null,

            /*
             * Não assumimos que a condição é a solicitada.
             * Tentamos identificar pelo título.
             */
            'condition' => $this->resolveCondition($title),

            'availability' => true,
            'available_quantity' => null,

            'rating' => isset($item['rating'])
                && is_numeric($item['rating'])
                    ? (float) $item['rating']
                    : null,

            'reviews' => isset($item['reviews'])
                && is_numeric($item['reviews'])
                    ? (int) $item['reviews']
                    : null,

            'delivery_description' => $delivery,

            'source' => 'serpapi',
        ];
    }

    private function normalizeUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        /*
         * O product_link do Google Shopping pode conter
         * espaços, que fazem FILTER_VALIDATE_URL falhar.
         */
        $url = preg_replace('/\s+/', '%20', $url);

        if (! is_string($url)) {
            return null;
        }

        return $url;
    }

    private function isValidUrl(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        return in_array(
            $parts['scheme'] ?? null,
            ['http', 'https'],
            true
        ) && ! empty($parts['host']);
    }

    private function resolveBrand(
        string $title,
        ?string $requestedBrand
    ): ?string {
        if (! $requestedBrand) {
            return null;
        }

        return Str::contains(
            Str::lower($title),
            Str::lower($requestedBrand)
        ) ? $requestedBrand : null;
    }

    private function resolveCondition(string $title): ?string
    {
        $normalizedTitle = Str::lower($title);

        if (
            Str::contains(
                $normalizedTitle,
                ['usado', 'seminovo', 'recondicionado', 'refurbished']
            )
        ) {
            return 'usado';
        }

        if (Str::contains($normalizedTitle, ['novo', 'lacrado'])) {
            return 'novo';
        }

        return null;
    }

    private function extractPrice(mixed $price): ?float
    {
        if (is_numeric($price)) {
            return (float) $price;
        }

        if (! is_string($price)) {
            return null;
        }

        $normalized = preg_replace('/[^\d,.]/', '', $price);

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        /*
         * Exemplos:
         * R$ 3.999,90 -> 3999.90
         * R$ 3.999    -> 3999.00
         * R$ 3999,90  -> 3999.90
         */
        if (
            str_contains($normalized, '.') &&
            str_contains($normalized, ',')
        ) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        } elseif (
            preg_match('/^\d{1,3}(\.\d{3})+$/', $normalized)
        ) {
            $normalized = str_replace('.', '', $normalized);
        }

        return is_numeric($normalized)
            ? (float) $normalized
            : null;
    }

    private function isValidItem(
        array $item,
        array $filters
    ): bool {
        if (
            empty($item['title']) ||
            ! $this->isValidUrl($item['url'] ?? null) ||
            ! is_numeric($item['price']) ||
            (float) $item['price'] <= 0
        ) {
            return false;
        }

        $price = (float) $item['price'];

        if (
            isset($filters['preco_minimo']) &&
            $filters['preco_minimo'] !== null &&
            $price < (float) $filters['preco_minimo']
        ) {
            return false;
        }

        if (
            isset($filters['preco_maximo']) &&
            $filters['preco_maximo'] !== null &&
            $price > (float) $filters['preco_maximo']
        ) {
            return false;
        }

        if (
            ! empty($filters['marca']) &&
            ! Str::contains(
                Str::lower($item['title']),
                Str::lower($filters['marca'])
            )
        ) {
            return false;
        }

        /*
         * Se o usuário pediu novo, elimina somente os itens
         * identificados explicitamente como usados.
         */
        if (
            ($filters['condicao'] ?? null) === 'novo' &&
            ($item['condition'] ?? null) === 'usado'
        ) {
            return false;
        }

        return true;
    }
}