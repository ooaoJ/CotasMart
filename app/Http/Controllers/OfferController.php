<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Offer::with(['product:id,name,model', 'source:id,name'])
                ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
                ->orderBy('current_price')
                ->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['last_checked_at'] ??= now();
        return response()->json(Offer::create($data)->load(['product', 'source', 'priceSnapshots']), 201);
    }

    public function show(Offer $offer): JsonResponse
    {
        return response()->json($offer->load(['product', 'source', 'priceSnapshots' => fn ($q) => $q->latest('collected_at')]));
    }

    public function update(Request $request, Offer $offer): JsonResponse
    {
        $data = $this->validated($request, false);
        $data['last_checked_at'] ??= now();
        $offer->update($data);
        return response()->json($offer->fresh()->load(['product', 'source', 'priceSnapshots']));
    }

    public function destroy(Offer $offer): JsonResponse
    {
        $offer->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'product_id' => [$required, 'exists:products,id'],
            'source_id' => [$required, 'exists:sources,id'],
            'external_identifier' => ['nullable', 'string', 'max:255'],
            'title' => [$required, 'string', 'max:255'],
            'seller' => ['nullable', 'string', 'max:255'],
            'url' => [$required, 'url'],
            'current_price' => [$required, 'numeric', 'min:0'],
            'shipping_price' => ['nullable', 'numeric', 'min:0'],
            'installment_price' => ['nullable', 'numeric', 'min:0'],
            'payment_condition' => ['nullable', 'string', 'max:255'],
            'availability' => ['sometimes', 'boolean'],
            'last_checked_at' => ['nullable', 'date'],
        ]);
    }
}

