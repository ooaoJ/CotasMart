<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name'])
            ->withMin(['offers as lowest_price' => fn ($q) => $q->where('availability', true)], 'current_price')
            ->withCount('offers')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('q'), function ($builder) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $builder->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('model', 'like', $term));
            })
            ->orderByDesc('popularity_score')
            ->paginate(20);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateProduct($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        return response()->json(Product::create($data)->load(['category', 'brand']), 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load([
            'category', 'brand',
            'offers' => fn ($q) => $q->with('source')->orderBy('current_price'),
            'offers.priceSnapshots' => fn ($q) => $q->orderBy('collected_at'),
        ]));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validateProduct($request, $product);
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }
        $product->update($data);
        return response()->json($product->fresh()->load(['category', 'brand']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(null, 204);
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['sometimes', 'required', 'string', 'max:180'],
            'model' => ['nullable', 'string', 'max:120'],
            'gtin' => ['nullable', 'string', 'max:14', Rule::unique('products')->ignore($product)],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'array'],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'pending'])],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }
        return $slug;
    }
}

