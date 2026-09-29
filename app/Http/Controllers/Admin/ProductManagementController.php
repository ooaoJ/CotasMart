<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductManagementController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with(['category', 'brand'])->withCount('offers')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%')->orWhere('model', 'like', '%' . $request->q . '%'))
            ->latest()->paginate(15)->withQueryString();
        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product(), 'categories' => Category::orderBy('name')->get(), 'brands' => Brand::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['specifications'] = $this->specifications($request);
        Product::create($data);
        return redirect()->route('admin.produtos.index')->with('success', 'Produto cadastrado.');
    }

    public function edit(Product $produto): View
    {
        return view('admin.products.form', ['product' => $produto, 'categories' => Category::orderBy('name')->get(), 'brands' => Brand::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $produto): RedirectResponse
    {
        $data = $this->validated($request, $produto);
        if ($data['name'] !== $produto->name) $data['slug'] = $this->uniqueSlug($data['name'], $produto->id);
        $data['specifications'] = $this->specifications($request);
        $produto->update($data);
        return redirect()->route('admin.produtos.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Product $produto): RedirectResponse
    {
        $produto->delete();
        return back()->with('success', 'Produto removido.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'], 'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:180'], 'model' => ['nullable', 'string', 'max:120'],
            'gtin' => ['nullable', 'string', 'max:14', Rule::unique('products')->ignore($product)],
            'description' => ['nullable', 'string'], 'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
        ]);
    }

    private function specifications(Request $request): array
    {
        return array_filter(['processor' => $request->processor, 'ram_gb' => $request->ram_gb, 'storage_gb' => $request->storage_gb, 'display' => $request->display], fn ($v) => filled($v));
    }

    private function uniqueSlug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name); $slug = $base; $number = 2;
        while (Product::where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) $slug = $base . '-' . $number++;
        return $slug;
    }
}

