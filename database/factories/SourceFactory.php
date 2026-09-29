<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SourceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'website_url' => fake()->url(),
            'logo' => null,
            'source_type' => 'store',
            'reliability_score' => fake()->randomFloat(2, 80, 100),
            'active' => true,
            'last_success_at' => fake()->optional()->dateTimeBetween('-7 days'),
        ];
    }
}

