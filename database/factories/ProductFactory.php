<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $model = strtoupper(fake()->bothify('??-####'));
        $name = fake()->randomElement(['Notebook', 'Monitor', 'SSD', 'Roteador', 'Impressora']) . " {$model}";

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('####'),
            'model' => $model,
            'gtin' => null,
            'description' => fake()->paragraph(),
            'specifications' => [
                'ram_gb' => fake()->randomElement([8, 16, 32]),
                'storage_gb' => fake()->randomElement([256, 512, 1024]),
            ],
            'image' => null,
            'popularity_score' => fake()->numberBetween(0, 100),
            'search_count' => fake()->numberBetween(0, 50),
            'last_searched_at' => fake()->optional()->dateTimeBetween('-30 days'),
            'last_updated_at' => fake()->optional()->dateTimeBetween('-7 days'),
            'next_update_at' => fake()->optional()->dateTimeBetween('now', '+7 days'),
            'status' => 'active',
        ];
    }
}

