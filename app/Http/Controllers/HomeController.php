<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Search;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $popular = Product::with(['brand:id,name', 'offers' => fn ($q) => $q->where('availability', true)->orderBy('current_price')])
            ->where('status', 'active')->orderByDesc('popularity_score')->limit(8)->get();
        $categories = Category::where('active', true)->withCount('products')->orderByDesc('products_count')->limit(8)->get();

        return view('home', compact('popular', 'categories'));
    }

    public function search(Request $request): View
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:255']]);
        $query = trim($request->string('q')->toString());
        $term = "%{$query}%";

        $products = Product::with(['brand:id,name', 'category:id,name'])
            ->withMin(['offers as lowest_price' => fn ($q) => $q->where('availability', true)], 'current_price')
            ->withCount(['offers' => fn ($q) => $q->where('availability', true)])
            ->where('status', 'active')
            ->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', $term));
            })->paginate(16)->withQueryString();

        Search::create([
            'user_id' => $request->user()?->id,
            'product_id' => $products->total() === 1 ? $products->first()?->id : null,
            'query' => $query,
            'normalized_query' => Str::lower(Str::ascii($query)),
            'results_count' => $products->total(),
            'searched_at' => now(),
        ]);

        Product::whereKey($products->pluck('id'))->increment('search_count');
        Product::whereKey($products->pluck('id'))->increment('popularity_score', 3);
        Product::whereKey($products->pluck('id'))->update(['last_searched_at' => now()]);

        return view('products.search', compact('products', 'query'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'active' || auth()->check(), 404);
        $product->load([
            'brand', 'category',
            'offers' => fn ($q) => $q->where('availability', true)->with('source')->orderBy('current_price'),
            'offers.priceSnapshots' => fn ($q) => $q->orderBy('collected_at'),
        ]);

        $history = $product->offers->flatMap->priceSnapshots->sortBy('collected_at')->values();
        $chartData = $history->map(fn ($snapshot) => [
            'date' => $snapshot->collected_at?->format('d/m'),
            'price' => (float) $snapshot->total_price,
        ])->values();

        return view('products.show', compact('product', 'history', 'chartData'));
    }
}
