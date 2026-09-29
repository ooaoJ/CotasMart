<?php

namespace Database\Factories;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceSnapshotFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 100, 7000);
        $shipping = fake()->randomFloat(2, 0, 150);

        return [
            'offer_id' => Offer::factory(),
            'price' => $price,
            'shipping_price' => $shipping,
            'total_price' => $price + $shipping,
            'availability' => true,
            'collected_at' => fake()->dateTimeBetween('-90 days'),
        ];
    }
}

