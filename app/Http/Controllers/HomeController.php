<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Search;
use App\Services\CollectedProductImporter;
use App\Services\Collectors\ProductCollectorInterface;
use App\Services\GroqService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function index(): View
    {
        $popular = Product::with([
            'brand:id,name',
            'offers' => fn ($query) => $query
                ->where('availability', true)
                ->orderBy('current_price'),
        ])
            ->where('status', 'active')
            ->orderByDesc('popularity_score')
            ->limit(8)
            ->get();

        $categories = Category::where('active', true)
            ->withCount('products')
            ->orderByDesc('products_count')
            ->limit(8)
            ->get();

        return view('home', compact(
            'popular',
            'categories'
        ));
    }

    public function search(
        Request $request,
        GroqService $groq,
        ProductCollectorInterface $collector,
        CollectedProductImporter $importer
    ): View {
        $validated = $request->validate([
            'q' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
        ]);

        $query = trim($validated['q']);

        $normalizedQuery = Str::of($query)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();

        /*
         * Verifica antes de criar a pesquisa atual.
         * Isso evita consumir a SerpApi repetidamente quando
         * uma consulta não encontrou nenhum produto.
         */
        $recentSameSearchExists = Search::query()
            ->where('normalized_query', $normalizedQuery)
            ->where('searched_at', '>=', now()->subHours(3))
            ->whereIn('status', [
                Search::STATUS_COMPLETED,
                Search::STATUS_FAILED,
            ])
            ->exists();

        $search = Search::create([
            'user_id' => $request->user()->id,
            'product_id' => null,
            'query' => $query,
            'normalized_query' => $normalizedQuery,
            'status' => Search::STATUS_PENDING,
            'filters' => [],
            'results_count' => 0,
            'error_message' => null,
            'searched_at' => now(),
        ]);

        $search->markAsProcessing();

        $filters = [];
        $warning = null;
        $externalSearchPerformed = false;
        $importSummary = null;

        /*
         * Primeiro, a IA interpreta a pesquisa.
         */
        try {
            $filters = $groq->analisarPesquisa($query);

            $search->update([
                'filters' => $filters,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            /*
             * Mesmo que a Groq falhe, ainda tentamos fazer uma
             * busca comum pelo texto informado.
             */
            $warning = 'Não foi possível interpretar todos os filtros da pesquisa. Foi realizada uma busca por texto.';
        }

        /*
         * Consulta inicial ao banco.
         */
        $databaseQuery = $this->buildProductQuery(
            $query,
            $filters
        );

        $databaseResultsCount = (
            clone $databaseQuery
        )->count();

        $hasRecentProducts = (
            clone $databaseQuery
        )
            ->where(
                'last_updated_at',
                '>=',
                now()->subHours(12)
            )
            ->exists();

        /*
         * Consulta externa somente quando:
         *
         * 1. Nenhum produto foi encontrado; ou
         * 2. Os produtos encontrados estão desatualizados.
         *
         * A mesma pesquisa não tenta novamente durante 3 horas.
         */
        $shouldCollectExternally = (
            $databaseResultsCount === 0 ||
            ! $hasRecentProducts
        ) && ! $recentSameSearchExists;

        if ($shouldCollectExternally) {
            try {
                $externalSearchPerformed = true;

                $collectedOffers = $collector->search(
                    $query,
                    $filters
                );

                $importSummary = $importer->import(
                    $collectedOffers,
                    $filters
                );
            } catch (Throwable $exception) {
                report($exception);

                $warning = $databaseResultsCount > 0
                    ? 'Os produtos salvos foram exibidos, mas não foi possível atualizar os preços agora.'
                    : 'Não foi possível consultar novas ofertas agora. Tente novamente mais tarde.';
            }
        }

        /*
         * Executa uma nova consulta porque a SerpApi pode ter
         * acabado de inserir produtos e ofertas.
         */
        $products = $this->buildProductQuery(
            $query,
            $filters
        )
            ->paginate(16)
            ->withQueryString();

        $productIds = $products->getCollection()
            ->pluck('id');

        if ($productIds->isNotEmpty()) {
            Product::whereKey($productIds)->increment(
                'search_count'
            );

            Product::whereKey($productIds)->increment(
                'popularity_score',
                3
            );

            Product::whereKey($productIds)->update([
                'last_searched_at' => now(),
            ]);
        }

        $search->update([
            'product_id' => $products->total() === 1
                ? $products->first()?->id
                : null,
        ]);

        if (
            $products->total() === 0 &&
            $warning !== null
        ) {
            $search->markAsFailed($warning);
        } else {
            $search->markAsCompleted(
                $products->total()
            );
        }

        return view('products.search', [
            'products' => $products,
            'query' => $query,
            'filters' => $filters,
            'warning' => $warning,
            'externalSearchPerformed' =>
                $externalSearchPerformed,
            'importSummary' => $importSummary,
        ]);
    }

    private function buildProductQuery(
        string $originalQuery,
        array $filters
    ): Builder {
        $query = Product::query()
            ->with([
                'brand:id,name',
                'category:id,name',
                'offers' => fn ($offerQuery) =>
                    $offerQuery
                        ->where('availability', true)
                        ->with('source:id,name,slug')
                        ->orderBy('current_price'),
            ])
            ->withMin(
                [
                    'offers as lowest_price' =>
                        fn ($offerQuery) =>
                            $offerQuery->where(
                                'availability',
                                true
                            ),
                ],
                'current_price'
            )
            ->withCount([
                'offers' => fn ($offerQuery) =>
                    $offerQuery->where(
                        'availability',
                        true
                    ),
            ])
            ->where('status', 'active')
            ->whereHas(
                'offers',
                fn ($offerQuery) =>
                    $offerQuery->where(
                        'availability',
                        true
                    )
            );

        $productName = trim((string) (
            $filters['produto'] ?? ''
        ));

        $brand = trim((string) (
            $filters['marca'] ?? ''
        ));

        $model = trim((string) (
            $filters['modelo'] ?? ''
        ));

        $processor = trim((string) (
            $filters['processador'] ?? ''
        ));

        /*
         * Se a IA conseguiu identificar campos específicos,
         * usamos esses campos em vez de pesquisar a frase inteira.
         */
        $hasStructuredFilters =
            $productName !== '' ||
            $brand !== '' ||
            $model !== '' ||
            $processor !== '' ||
            ! empty($filters['ram_gb']) ||
            ! empty($filters['storage_gb']);

        if ($productName !== '') {
            $query->where(function (Builder $builder) use (
                $productName
            ): void {
                $term = '%' . $productName . '%';

                $builder
                    ->where('name', 'like', $term)
                    ->orWhereHas(
                        'category',
                        fn (Builder $categoryQuery) =>
                            $categoryQuery->where(
                                'name',
                                'like',
                                $term
                            )
                    );
            });
        }

        if ($brand !== '') {
            $query->whereHas(
                'brand',
                fn (Builder $brandQuery) =>
                    $brandQuery->where(
                        'name',
                        'like',
                        '%' . $brand . '%'
                    )
            );
        }

        if ($model !== '') {
            $query->where(function (Builder $builder) use (
                $model
            ): void {
                $term = '%' . $model . '%';

                $builder
                    ->where('model', 'like', $term)
                    ->orWhere('name', 'like', $term);
            });
        }

        if ($processor !== '') {
            $query->where(function (Builder $builder) use (
                $processor
            ): void {
                $term = '%' . $processor . '%';

                $builder
                    ->where('name', 'like', $term)
                    ->orWhereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(specifications, '$.processor')) LIKE ?",
                        [$term]
                    );
            });
        }

        if (! empty($filters['ram_gb'])) {
            $query->whereRaw(
                "CAST(JSON_UNQUOTE(JSON_EXTRACT(specifications, '$.ram_gb')) AS UNSIGNED) = ?",
                [(int) $filters['ram_gb']]
            );
        }

        if (! empty($filters['storage_gb'])) {
            $query->whereRaw(
                "CAST(JSON_UNQUOTE(JSON_EXTRACT(specifications, '$.storage_gb')) AS UNSIGNED) = ?",
                [(int) $filters['storage_gb']]
            );
        }

        if (
            isset($filters['preco_minimo']) &&
            $filters['preco_minimo'] !== null
        ) {
            $minimumPrice = (float) $filters[
                'preco_minimo'
            ];

            $query->whereHas(
                'offers',
                fn (Builder $offerQuery) =>
                    $offerQuery
                        ->where('availability', true)
                        ->where(
                            'current_price',
                            '>=',
                            $minimumPrice
                        )
            );
        }

        if (
            isset($filters['preco_maximo']) &&
            $filters['preco_maximo'] !== null
        ) {
            $maximumPrice = (float) $filters[
                'preco_maximo'
            ];

            $query->whereHas(
                'offers',
                fn (Builder $offerQuery) =>
                    $offerQuery
                        ->where('availability', true)
                        ->where(
                            'current_price',
                            '<=',
                            $maximumPrice
                        )
            );
        }

        /*
         * Caso a Groq tenha falhado ou não identificado nenhum
         * filtro, realiza a busca textual tradicional.
         */
        if (! $hasStructuredFilters) {
            $words = collect(
                preg_split(
                    '/\s+/',
                    Str::lower(Str::ascii($originalQuery))
                )
            )
                ->filter(
                    fn (string $word): bool =>
                        strlen($word) >= 3
                )
                ->reject(
                    fn (string $word): bool =>
                        in_array($word, [
                            'quero',
                            'com',
                            'uma',
                            'para',
                            'ate',
                            'reais',
                            'produto',
                        ], true)
                )
                ->values();

            $query->where(function (
                Builder $builder
            ) use ($words): void {
                foreach ($words as $word) {
                    $term = '%' . $word . '%';

                    $builder->orWhere(
                        'name',
                        'like',
                        $term
                    )
                        ->orWhere(
                            'model',
                            'like',
                            $term
                        )
                        ->orWhereHas(
                            'brand',
                            fn (Builder $brandQuery) =>
                                $brandQuery->where(
                                    'name',
                                    'like',
                                    $term
                                )
                        );
                }
            });
        }

        return $query
            ->orderByRaw(
                'lowest_price IS NULL'
            )
            ->orderBy('lowest_price');
    }

    public function show(Product $product): View
    {
        abort_unless(
            $product->status === 'active' ||
            auth()->check(),
            404
        );

        $product->load([
            'brand',
            'category',

            'offers' => fn ($query) =>
                $query
                    ->where('availability', true)
                    ->with('source')
                    ->orderBy('current_price'),

            'offers.priceSnapshots' => fn ($query) =>
                $query->orderBy('collected_at'),
        ]);

        $history = $product->offers
            ->flatMap
            ->priceSnapshots
            ->sortBy('collected_at')
            ->values();

        $chartData = $history
            ->map(fn ($snapshot) => [
                'date' => $snapshot
                    ->collected_at
                    ?->format('d/m'),

                'price' => (float)
                    $snapshot->total_price,
            ])
            ->values();

        return view('products.show', compact(
            'product',
            'history',
            'chartData'
        ));
    }
}