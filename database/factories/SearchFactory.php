<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SearchFactory extends Factory
{
    public function definition(): array
    {
        $query = fake()->randomElement([
            'notebook acer', 'monitor 24 polegadas', 'ssd 512gb',
            'roteador gigabit', 'impressora laser',
        ]);

        return [
            'user_id' => fake()->boolean(60) ? User::factory() : null,
            'product_id' => fake()->boolean(70) ? Product::factory() : null,
            'query' => $query,
            'normalized_query' => Str::lower(Str::ascii($query)),
            'results_count' => fake()->numberBetween(0, 15),
            'searched_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}

