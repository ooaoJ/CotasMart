<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\GroqService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiController extends Controller
{
    public function analisar(
        Request $request,
        GroqService $groq
    ): JsonResponse {
        $dados = $request->validate([
            'pesquisa' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        try {
            /*
             * 1. Interpreta a pesquisa utilizando a IA
             */
            $filtros = $groq->analisarPesquisa(
                $dados['pesquisa']
            );

            /*
             * 2. Inicia a consulta dos produtos
             */
            $consulta = Product::query()
                ->where('status', 'active');

            /*
             * 3. Filtra pelo tipo/nome do produto
             */
            if (! empty($filtros['produto'])) {
                $produtoPesquisado = $filtros['produto'];

                $consulta->where(
                    function (Builder $query) use ($produtoPesquisado) {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$produtoPesquisado}%"
                            )
                            ->orWhere(
                                'model',
                                'like',
                                "%{$produtoPesquisado}%"
                            )
                            ->orWhere(
                                'description',
                                'like',
                                "%{$produtoPesquisado}%"
                            );
                    }
                );
            }

            /*
             * 4. Filtra pela categoria
             *
             * "notebook" também encontra "Notebooks".
             */
            if (! empty($filtros['categoria'])) {
                $categoria = $filtros['categoria'];

                $consulta->whereHas(
                    'category',
                    function (Builder $query) use ($categoria) {
                        $query
                            ->where('active', true)
                            ->where(
                                'name',
                                'like',
                                "%{$categoria}%"
                            );
                    }
                );
            }

            /*
             * 5. Filtra pela marca
             */
            if (! empty($filtros['marca'])) {
                $marca = $filtros['marca'];

                $consulta->whereHas(
                    'brand',
                    function (Builder $query) use ($marca) {
                        $query
                            ->where('active', true)
                            ->where(
                                'name',
                                'like',
                                "%{$marca}%"
                            );
                    }
                );
            }

            /*
             * 6. Filtra pelo modelo
             */
            if (! empty($filtros['modelo'])) {
                $modelo = $filtros['modelo'];

                $consulta->where(
                    'model',
                    'like',
                    "%{$modelo}%"
                );
            }

            /*
             * 7. Filtra pela memória RAM.
             *
             * O CAST garante comparação numérica.
             * Dessa maneira 16 não será considerado maior que 32.
             */
            if (
                isset($filtros['ram_gb']) &&
                $filtros['ram_gb'] !== null
            ) {
                $consulta->whereRaw(
                    "CAST(
                        JSON_UNQUOTE(
                            JSON_EXTRACT(
                                products.specifications,
                                '$.ram_gb'
                            )
                        ) AS UNSIGNED
                    ) >= ?",
                    [(int) $filtros['ram_gb']]
                );
            }

            /*
             * 8. Filtra pelo armazenamento
             */
            if (
                isset($filtros['storage_gb']) &&
                $filtros['storage_gb'] !== null
            ) {
                $consulta->whereRaw(
                    "CAST(
                        JSON_UNQUOTE(
                            JSON_EXTRACT(
                                products.specifications,
                                '$.storage_gb'
                            )
                        ) AS UNSIGNED
                    ) >= ?",
                    [(int) $filtros['storage_gb']]
                );
            }

            /*
             * 9. Filtra pelo processador
             */
            if (
                isset($filtros['processador']) &&
                $filtros['processador'] !== null
            ) {
                $consulta->whereRaw(
                    "JSON_UNQUOTE(
                        JSON_EXTRACT(
                            products.specifications,
                            '$.processor'
                        )
                    ) LIKE ?",
                    ['%' . $filtros['processador'] . '%']
                );
            }

            /*
             * 10. Exige pelo menos uma oferta disponível
             * dentro da faixa de preço.
             */
            $consulta->whereHas(
                'offers',
                function (Builder $query) use ($filtros) {
                    $this->aplicarFiltrosOferta(
                        $query,
                        $filtros
                    );
                }
            );

            /*
             * 11. Carrega os relacionamentos.
             *
             * Somente as ofertas disponíveis e dentro da
             * faixa de preço serão retornadas.
             */
            $consulta->with([
                'brand',
                'category',

                'offers' => function (Builder $query) use ($filtros) {
                    $this->aplicarFiltrosOferta(
                        $query,
                        $filtros
                    );

                    $query
                        ->with('source')
                        ->orderBy('current_price');
                },
            ]);

            /*
             * 12. Executa a consulta
             */
            $produtos = $consulta
                ->limit(20)
                ->get();

            /*
             * 13. Atualiza as informações de popularidade
             */
            foreach ($produtos as $produto) {
                $produto->increment('search_count');

                $produto->update([
                    'last_searched_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'provider' => 'groq',
                'pesquisa_original' => $dados['pesquisa'],
                'filtros' => $filtros,
                'quantidade' => $produtos->count(),
                'produtos' => $produtos,
            ]);
        } catch (Throwable $erro) {
            report($erro);

            return response()->json([
                'success' => false,
                'message' => $erro->getMessage(),
            ], 500);
        }
    }

    /**
     * Aplica disponibilidade e faixa de preço às ofertas.
     */
    private function aplicarFiltrosOferta(
        Builder $query,
        array $filtros
    ): void {
        $query->where('availability', true);

        if (
            isset($filtros['preco_minimo']) &&
            $filtros['preco_minimo'] !== null
        ) {
            $query->where(
                'current_price',
                '>=',
                (float) $filtros['preco_minimo']
            );
        }

        if (
            isset($filtros['preco_maximo']) &&
            $filtros['preco_maximo'] !== null
        ) {
            $query->where(
                'current_price',
                '<=',
                (float) $filtros['preco_maximo']
            );
        }
    }
}