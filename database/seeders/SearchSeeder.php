<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Search;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SearchSeeder extends Seeder
{
    public function run(): void
    {
        Product::all()->each(function (Product $product) {
            Search::create([
                'product_id' => $product->id,
                'query' => $product->name,
                'normalized_query' => Str::lower(Str::ascii($product->name)),
                'results_count' => 1,
                'searched_at' => now()->subDays(random_int(0, 20)),
            ]);
        });
    }
}

