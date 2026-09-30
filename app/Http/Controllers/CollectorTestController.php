<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CollectedProductImporter;
use App\Services\Collectors\ProductCollectorInterface;
use App\Services\GroqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CollectorTestController extends Controller
{
    public function search(
        Request $request,
        GroqService $groq,
        ProductCollectorInterface $collector,
        CollectedProductImporter $importer
    ): JsonResponse {
        $data = $request->validate([
            'pesquisa' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        try {
            $filters = $groq->analisarPesquisa(
                $data['pesquisa']
            );

            $collectedOffers = $collector->search(
                $data['pesquisa'],
                $filters
            );

            $import = $importer->import(
                $collectedOffers,
                $filters
            );

            $products = Product::query()
                ->with([
                    'brand',
                    'category',
                    'offers.source',
                ])
                ->whereIn(
                    'id',
                    $import['product_ids']
                )
                ->get();

            return response()->json([
                'success' => true,

                'collector' => config(
                    'product-search.collector'
                ),

                'query' => $data['pesquisa'],
                'filters' => $filters,

                'collection' => [
                    'offers_received' =>
                        count($collectedOffers),

                    'offers' => $collectedOffers,
                ],

                'database' => $import,

                'products' => $products,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}