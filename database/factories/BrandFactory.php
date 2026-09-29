<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Acer', 'Dell', 'Lenovo', 'Samsung', 'ASUS',
            'LG', 'Kingston', 'Logitech', 'TP-Link', 'HP',
        ]);

        return ['name' => $name, 'slug' => Str::slug($name), 'active' => true];
    }
}

