<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Acer', 'Dell', 'Lenovo', 'Samsung', 'ASUS', 'LG', 'Kingston', 'Logitech', 'TP-Link', 'HP'] as $name) {
            Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'active' => true]);
        }
    }
}

