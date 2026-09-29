<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Brand::withCount('products')->orderBy('name')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $data['slug'] = Str::slug($data['name']);
        return response()->json(Brand::create($data), 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        return response()->json($brand->loadCount('products'));
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('brands')->ignore($brand)],
            'active' => ['sometimes', 'boolean'],
        ]);
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $brand->update($data);
        return response()->json($brand->fresh());
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->exists()) {
            return response()->json(['message' => 'A marca possui produtos vinculados.'], 422);
        }
        $brand->delete();
        return response()->json(null, 204);
    }
}

