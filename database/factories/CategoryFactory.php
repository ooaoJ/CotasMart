<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Notebooks', 'Monitores', 'Impressoras', 'Armazenamento',
            'Periféricos', 'Redes', 'Celulares', 'Componentes',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'active' => true,
        ];
    }
}

