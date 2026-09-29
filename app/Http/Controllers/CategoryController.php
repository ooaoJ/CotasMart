<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Category::withCount('products')->orderBy('name')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['name']);

        return response()->json(Category::create($data), 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category->loadCount('products'));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('categories')->ignore($category)],
            'description' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $category->update($data);

        return response()->json($category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'A categoria possui produtos vinculados.'], 422);
        }
        $category->delete();
        return response()->json(null, 204);
    }
}

