<?php

namespace Database\Seeders;

use App\Models\Offer;
use Illuminate\Database\Seeder;

class PriceSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        Offer::all()->each(function (Offer $offer) {
            foreach ([30, 20, 10] as $daysAgo) {
                $price = (float) $offer->current_price * (1 + ($daysAgo / 1000));
                $shipping = (float) ($offer->shipping_price ?? 0);

                $offer->priceSnapshots()->firstOrCreate(
                    ['collected_at' => now()->subDays($daysAgo)->startOfDay()],
                    [
                        'price' => $price,
                        'shipping_price' => $shipping,
                        'total_price' => $price + $shipping,
                        'availability' => true,
                    ]
                );
            }
        });
    }
}

