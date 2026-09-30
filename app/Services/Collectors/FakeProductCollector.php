<?php

namespace App\Services\Collectors;

class FakeProductCollector implements ProductCollectorInterface
{
    public function search(
        string $query,
        array $filters = []
    ): array {
        $brand = $filters['marca'] ?? 'Dell';
        $model = $filters['modelo'] ?? 'Inspiron 15';
        $ram = $filters['ram_gb'] ?? 16;
        $storage = $filters['storage_gb'] ?? 512;

        $title = sprintf(
            'Notebook %s %s %dGB RAM SSD %dGB',
            $brand,
            $model,
            $ram,
            $storage
        );

        return [
            $this->makeOffer(
                'FAKE-001',
                $title,
                $brand,
                $model,
                3699.90,
                0,
                'Loja Alpha'
            ),

            $this->makeOffer(
                'FAKE-002',
                $title,
                $brand,
                $model,
                3799.90,
                19.90,
                'Loja Beta'
            ),

            $this->makeOffer(
                'FAKE-003',
                $title,
                $brand,
                $model,
                3899.90,
                29.90,
                'Loja Gamma'
            ),
        ];
    }

    private function makeOffer(
        string $id,
        string $title,
        string $brand,
        string $model,
        float $price,
        float $shippingPrice,
        string $store
    ): array {
        return [
            'external_identifier' => $id,
            'catalog_product_id' => 'FAKE-PRODUCT-001',
            'title' => $title,
            'brand' => $brand,
            'model' => $model,
            'gtin' => '7890000000001',
            'price' => $price,
            'shipping_price' => $shippingPrice,
            'free_shipping' => $shippingPrice === 0.0,
            'installment_price' => $price / 10,
            'installments_quantity' => 10,
            'seller' => $store,
            'store' => $store,
            'url' => 'https://example.com/' . strtolower($id),
            'image' => null,
            'condition' => 'novo',
            'availability' => true,
            'available_quantity' => 10,
            'source' => 'fake',
        ];
    }
}