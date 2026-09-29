<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('admin.catalog.index', ['categories' => Category::withCount('products')->orderBy('name')->get(), 'brands' => Brand::withCount('products')->orderBy('name')->get(), 'sources' => Source::withCount('offers')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'max:100', 'unique:categories,name']]);
        Category::create($data + ['slug' => Str::slug($data['name']), 'active' => true]); return back()->with('success', 'Categoria criada.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) return back()->withErrors(['catalog' => 'Categoria possui produtos.']);
        $category->delete(); return back()->with('success', 'Categoria removida.');
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'max:100', 'unique:brands,name']]);
        Brand::create($data + ['slug' => Str::slug($data['name']), 'active' => true]); return back()->with('success', 'Marca criada.');
    }

    public function destroyBrand(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) return back()->withErrors(['catalog' => 'Marca possui produtos.']);
        $brand->delete(); return back()->with('success', 'Marca removida.');
    }

    public function storeSource(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'max:120', 'unique:sources,name'], 'website_url' => ['required', 'url']]);
        Source::create($data + ['slug' => Str::slug($data['name']), 'source_type' => 'store', 'active' => true]); return back()->with('success', 'Fonte criada.');
    }

    public function destroySource(Source $source): RedirectResponse
    {
        if ($source->offers()->exists()) return back()->withErrors(['catalog' => 'Fonte possui ofertas.']);
        $source->delete(); return back()->with('success', 'Fonte removida.');
    }
}

