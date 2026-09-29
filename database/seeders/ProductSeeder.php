<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['Notebooks', 'Acer', 'Notebook Acer Aspire 5 A515', 'A515-57-55B8', ['processor' => 'Intel Core i5', 'ram_gb' => 8, 'storage_gb' => 512]],
            ['Notebooks', 'Dell', 'Notebook Dell Inspiron 15', 'I15-I120K', ['processor' => 'Intel Core i5', 'ram_gb' => 16, 'storage_gb' => 512]],
            ['Monitores', 'LG', 'Monitor LG 24 polegadas Full HD', '24MP400', ['screen_inches' => 24, 'resolution' => '1920x1080']],
            ['Armazenamento', 'Kingston', 'SSD Kingston NV2 1TB NVMe', 'SNV2S-1000G', ['storage_gb' => 1000, 'interface' => 'NVMe']],
            ['Redes', 'TP-Link', 'Roteador TP-Link Gigabit Dual Band', 'ARCHER-C6', ['wifi' => 'dual-band', 'ports' => 'gigabit']],
        ];

        foreach ($products as [$category, $brand, $name, $model, $specifications]) {
            Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => Category::where('name', $category)->value('id'),
                    'brand_id' => Brand::where('name', $brand)->value('id'),
                    'name' => $name,
                    'model' => $model,
                    'description' => "Produto de demonstração: {$name}.",
                    'specifications' => $specifications,
                    'status' => 'active',
                ]
            );
        }
    }
}

