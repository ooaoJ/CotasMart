<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Notebooks', 'Monitores', 'Impressoras', 'Armazenamento', 'Periféricos', 'Redes'] as $name) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'active' => true]);
        }
    }
}

