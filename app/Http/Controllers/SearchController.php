<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Search::with(['user:id,name', 'product:id,name'])->latest('searched_at')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['query' => ['required', 'string', 'min:2', 'max:255']]);
        $query = trim($data['query']);
        $normalized = Str::lower(Str::ascii($query));
        $term = "%{$query}%";

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'offers' => fn ($q) => $q->where('availability', true)->with('source:id,name')->orderBy('current_price')])
            ->where('status', 'active')
            ->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', $term));
            })
            ->limit(20)
            ->get();

        $products->each(fn (Product $product) => $product->update([
            'search_count' => $product->search_count + 1,
            'popularity_score' => $product->popularity_score + 3,
            'last_searched_at' => now(),
        ]));

        $search = Search::create([
            'user_id' => $request->user()?->id,
            'product_id' => $products->count() === 1 ? $products->first()->id : null,
            'query' => $query,
            'normalized_query' => $normalized,
            'results_count' => $products->count(),
            'searched_at' => now(),
        ]);

        return response()->json(['search' => $search, 'products' => $products]);
    }
}

