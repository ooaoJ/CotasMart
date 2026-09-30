<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Source;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CollectedProductImporter
{
    public function import(
        array $collectedOffers,
        array $filters = []
    ): array {
        $summary = [
            'received' => count($collectedOffers),
            'products_created' => 0,
            'products_updated' => 0,
            'offers_created' => 0,
            'offers_updated' => 0,
            'ignored' => 0,
            'errors' => [],
            'product_ids' => [],
        ];

        foreach ($collectedOffers as $collectedOffer) {
            try {
                if (! is_array($collectedOffer)) {
                    $summary['ignored']++;

                    continue;
                }

                DB::transaction(function () use (
                    $collectedOffer,
                    $filters,
                    &$summary
                ): void {
                    $result = $this->importOffer(
                        $collectedOffer,
                        $filters
                    );

                    $summary[
                        $result['product_created']
                            ? 'products_created'
                            : 'products_updated'
                    ]++;

                    $summary[
                        $result['offer_created']
                            ? 'offers_created'
                            : 'offers_updated'
                    ]++;

                    $summary['product_ids'][] =
                        $result['product_id'];
                });
            } catch (Throwable $exception) {
                report($exception);

                $summary['ignored']++;

                $summary['errors'][] = [
                    'title' => $collectedOffer['title'] ?? null,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $summary['product_ids'] = array_values(
            array_unique($summary['product_ids'])
        );

        return $summary;
    }

    private function importOffer(
        array $data,
        array $filters
    ): array {
        $this->validateCollectedOffer($data);

        $category = $this->resolveCategory($filters);
        $brand = $this->resolveBrand($data, $filters);
        $source = $this->resolveSource($data);

        $externalCatalogId = $this->makeExternalCatalogId(
            $data
        );

        $product = Product::query()->firstOrNew([
            'external_catalog_id' => $externalCatalogId,
        ]);

        $productCreated = ! $product->exists;

        $product->fill([
            'category_id' => $category->id,
            'brand_id' => $brand?->id,
            'name' => $this->productName($data),
            'model' => $data['model'] ?? null,
            'gtin' => $data['gtin'] ?? null,
            'description' => $this->productDescription($data),
            'specifications' => $this->specifications($filters),
            'image' => $this->safeImage($data['image'] ?? null),
            'last_updated_at' => now(),
            'next_update_at' => now()->addHours(12),
            'status' => 'active',
        ]);

        if ($productCreated) {
            $product->slug = $this->uniqueProductSlug(
                $product->name,
                $externalCatalogId
            );
        }

        $product->save();

        $externalIdentifier = $this->externalIdentifier(
            $data
        );

        $offer = Offer::query()->firstOrNew([
            'source_id' => $source->id,
            'external_identifier' => $externalIdentifier,
        ]);

        $offerCreated = ! $offer->exists;

        $offer->fill([
            'product_id' => $product->id,
            'title' => Str::limit(
                trim((string) $data['title']),
                255,
                ''
            ),
            'seller' => Str::limit(
                trim((string) (
                    $data['seller']
                        ?? $data['store']
                        ?? $source->name
                )),
                255,
                ''
            ),
            'url' => trim((string) $data['url']),
            'current_price' => (float) $data['price'],
            'shipping_price' => isset($data['shipping_price'])
                && is_numeric($data['shipping_price'])
                    ? (float) $data['shipping_price']
                    : null,
            'installment_price' => isset(
                $data['installment_price']
            ) && is_numeric($data['installment_price'])
                ? (float) $data['installment_price']
                : null,
            'payment_condition' =>
                $this->paymentCondition($data),
            'availability' =>
                (bool) ($data['availability'] ?? true),
            'last_checked_at' => now(),
        ]);

        $offer->save();

        $source->update([
            'last_success_at' => now(),
            'active' => true,
        ]);

        return [
            'product_id' => $product->id,
            'offer_id' => $offer->id,
            'product_created' => $productCreated,
            'offer_created' => $offerCreated,
        ];
    }

    private function validateCollectedOffer(array $data): void
    {
        if (empty($data['title'])) {
            throw new RuntimeException(
                'A oferta não possui título.'
            );
        }

        if (
            ! isset($data['price']) ||
            ! is_numeric($data['price']) ||
            (float) $data['price'] <= 0
        ) {
            throw new RuntimeException(
                'A oferta não possui um preço válido.'
            );
        }

        if (empty($data['url'])) {
            throw new RuntimeException(
                'A oferta não possui URL.'
            );
        }

        if (
            empty($data['external_identifier']) &&
            empty($data['catalog_product_id'])
        ) {
            throw new RuntimeException(
                'A oferta não possui identificador externo.'
            );
        }
    }

    private function resolveCategory(array $filters): Category
    {
        $name = trim((string) (
            $filters['categoria']
                ?? $filters['produto']
                ?? 'Outros'
        ));

        if ($name === '') {
            $name = 'Outros';
        }

        $slug = Str::slug($name);

        return Category::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => Str::title($name),
                'description' => null,
                'active' => true,
            ]
        );
    }

    private function resolveBrand(
        array $data,
        array $filters
    ): ?Brand {
        $name = trim((string) (
            $data['brand']
                ?? $filters['marca']
                ?? ''
        ));

        if ($name === '') {
            return null;
        }

        return Brand::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => Str::title($name),
                'active' => true,
            ]
        );
    }

    private function resolveSource(array $data): Source
    {
        $storeName = trim((string) (
            $data['store']
                ?? $data['seller']
                ?? 'Google Shopping'
        ));

        if ($storeName === '') {
            $storeName = 'Google Shopping';
        }

        $storeName = Str::limit($storeName, 120, '');
        $slug = Str::limit(Str::slug($storeName), 140, '');

        if ($slug === '') {
            $slug = 'loja-' . substr(
                sha1($storeName),
                0,
                12
            );
        }

        return Source::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $storeName,
                'website_url' => $this->sourceWebsite(
                    $data['url'] ?? null
                ),
                'logo' => null,
                'source_type' => 'store',
                'reliability_score' => 80,
                'active' => true,
                'last_success_at' => now(),
            ]
        );
    }

    private function sourceWebsite(mixed $url): string
    {
        if (! is_string($url) || $url === '') {
            return 'https://www.google.com';
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return 'https://www.google.com';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            $scheme = 'https';
        }

        $website = $scheme . '://' . $host;

        return Str::limit($website, 255, '');
    }

    private function makeExternalCatalogId(array $data): string
    {
        if (! empty($data['catalog_product_id'])) {
            return 'serpapi:' . Str::limit(
                (string) $data['catalog_product_id'],
                170,
                ''
            );
        }

        $normalizedTitle = Str::of(
            (string) $data['title']
        )
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();

        return 'serpapi:title:' . sha1($normalizedTitle);
    }

    private function externalIdentifier(array $data): string
    {
        if (! empty($data['external_identifier'])) {
            return Str::limit(
                (string) $data['external_identifier'],
                255,
                ''
            );
        }

        return 'SERPAPI-' . sha1(
            ($data['url'] ?? '') .
            ($data['title'] ?? '') .
            ($data['store'] ?? '')
        );
    }

    private function productName(array $data): string
    {
        $title = trim((string) $data['title']);

        $title = preg_replace(
            '/\s*\((usado|novo|seminovo|recondicionado)\)\s*/iu',
            ' ',
            $title
        );

        $title = preg_replace('/\s+/', ' ', $title);

        return Str::limit(
            trim((string) $title),
            180,
            ''
        );
    }

    private function productDescription(array $data): string
    {
        $store = trim((string) (
            $data['store']
                ?? $data['seller']
                ?? 'loja não informada'
        ));

        return sprintf(
            'Produto coletado automaticamente. Oferta encontrada em %s.',
            $store
        );
    }

    private function specifications(array $filters): array
    {
        return array_filter(
            [
                'processor' =>
                    $filters['processador'] ?? null,
                'ram_gb' =>
                    $filters['ram_gb'] ?? null,
                'storage_gb' =>
                    $filters['storage_gb'] ?? null,
                'condition' =>
                    $filters['condicao'] ?? null,
            ],
            fn (mixed $value): bool =>
                $value !== null && $value !== ''
        );
    }

    private function safeImage(mixed $image): ?string
    {
        if (! is_string($image) || $image === '') {
            return null;
        }

        /*
         * A coluna image atualmente é VARCHAR(255).
         */
        return strlen($image) <= 255
            ? $image
            : null;
    }

    private function uniqueProductSlug(
        string $name,
        string $externalCatalogId
    ): string {
        $base = Str::limit(Str::slug($name), 185, '');

        if ($base === '') {
            $base = 'produto';
        }

        return $base . '-' . substr(
            sha1($externalCatalogId),
            0,
            10
        );
    }

    private function paymentCondition(array $data): ?string
    {
        if (
            empty($data['installments_quantity']) ||
            empty($data['installment_price'])
        ) {
            return null;
        }

        return sprintf(
            '%dx de R$ %.2f',
            (int) $data['installments_quantity'],
            (float) $data['installment_price']
        );
    }
}