<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Product;
use App\Models\Source;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $sources = Source::all();

        Product::all()->each(function (Product $product) use ($sources) {
            foreach ($sources as $index => $source) {
                $price = 500 + ($product->id * 350) + ($index * 75);

                Offer::updateOrCreate(
                    ['source_id' => $source->id, 'external_identifier' => "DEMO-{$product->id}-{$source->id}"],
                    [
                        'product_id' => $product->id,
                        'title' => $product->name,
                        'seller' => $source->name,
                        'url' => "{$source->website_url}/produto/{$product->slug}",
                        'current_price' => $price,
                        'shipping_price' => $index * 15,
                        'installment_price' => $price,
                        'payment_condition' => '10x sem juros',
                        'availability' => true,
                        'last_checked_at' => now(),
                    ]
                );
            }
        });
    }
}

