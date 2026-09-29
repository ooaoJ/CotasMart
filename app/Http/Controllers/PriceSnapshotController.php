<?php

namespace App\Http\Controllers;

use App\Models\PriceSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceSnapshotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            PriceSnapshot::with('offer:id,product_id,source_id,title')
                ->when($request->filled('offer_id'), fn ($q) => $q->where('offer_id', $request->integer('offer_id')))
                ->latest('collected_at')
                ->paginate(50)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['shipping_price'] ??= 0;
        $data['total_price'] = $data['price'] + $data['shipping_price'];
        $data['collected_at'] ??= now();
        return response()->json(PriceSnapshot::create($data), 201);
    }

    public function show(PriceSnapshot $priceSnapshot): JsonResponse
    {
        return response()->json($priceSnapshot->load('offer'));
    }

    public function update(Request $request, PriceSnapshot $priceSnapshot): JsonResponse
    {
        $data = $this->validated($request, false);
        $price = $data['price'] ?? (float) $priceSnapshot->price;
        $shipping = $data['shipping_price'] ?? (float) $priceSnapshot->shipping_price;
        $data['total_price'] = $price + $shipping;
        $priceSnapshot->update($data);
        return response()->json($priceSnapshot->fresh());
    }

    public function destroy(PriceSnapshot $priceSnapshot): JsonResponse
    {
        $priceSnapshot->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $creating = true): array
    {
        $required = $creating ? 'required' : 'sometimes';
        return $request->validate([
            'offer_id' => [$required, 'exists:offers,id'],
            'price' => [$required, 'numeric', 'min:0'],
            'shipping_price' => ['nullable', 'numeric', 'min:0'],
            'availability' => ['sometimes', 'boolean'],
            'collected_at' => ['nullable', 'date'],
        ]);
    }
}

