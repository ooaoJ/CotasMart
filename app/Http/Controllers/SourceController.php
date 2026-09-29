<?php

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Source::withCount('offers')->orderBy('name')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        return response()->json(Source::create($data), 201);
    }

    public function show(Source $source): JsonResponse
    {
        return response()->json($source->loadCount('offers'));
    }

    public function update(Request $request, Source $source): JsonResponse
    {
        $data = $this->validated($request, $source);
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $source->update($data);
        return response()->json($source->fresh());
    }

    public function destroy(Source $source): JsonResponse
    {
        if ($source->offers()->exists()) {
            return response()->json(['message' => 'A fonte possui ofertas vinculadas.'], 422);
        }
        $source->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request, ?Source $source = null): array
    {
        return $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120', Rule::unique('sources')->ignore($source)],
            'website_url' => ['sometimes', 'required', 'url', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'source_type' => ['sometimes', Rule::in(['store', 'marketplace', 'public_database', 'manual'])],
            'reliability_score' => ['sometimes', 'numeric', 'between:0,100'],
            'active' => ['sometimes', 'boolean'],
        ]);
    }
}

