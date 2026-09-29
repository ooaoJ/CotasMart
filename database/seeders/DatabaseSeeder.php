<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador', 'email' => 'admin@cotasmart.test', 'password' => 'password',
            'role' => 'admin', 'plan' => 'business', 'subscription_status' => 'active',
        ]);

        $this->call([
            CategorySeeder::class, BrandSeeder::class, SourceSeeder::class,
            ProductSeeder::class, OfferSeeder::class, PriceSnapshotSeeder::class, SearchSeeder::class,
        ]);
    }
}

