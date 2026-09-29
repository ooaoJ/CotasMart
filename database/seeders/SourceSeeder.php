<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Loja Alpha', 'Loja Beta', 'Loja Gamma'] as $name) {
            Source::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'website_url' => 'https://' . Str::slug($name) . '.example.com',
                    'source_type' => 'store',
                    'reliability_score' => 100,
                    'active' => true,
                ]
            );
        }
    }
}

