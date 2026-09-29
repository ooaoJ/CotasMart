<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfferFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 100, 7000);

        return [
            'product_id' => Product::factory(),
            'source_id' => Source::factory(),
            'external_identifier' => fake()->unique()->bothify('SKU-########'),
            'title' => fake()->sentence(6),
            'seller' => fake()->company(),
            'url' => fake()->url(),
            'current_price' => $price,
            'shipping_price' => fake()->randomFloat(2, 0, 150),
            'installment_price' => $price,
            'payment_condition' => '10x sem juros',
            'availability' => true,
            'last_checked_at' => now(),
        ];
    }
}

