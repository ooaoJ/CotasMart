<?php

namespace App\Providers;

use App\Services\Collectors\FakeProductCollector;
use App\Services\Collectors\MercadoLivreCollector;
use App\Services\Collectors\ProductCollectorInterface;
use App\Services\Collectors\SerpApiCollector;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductCollectorInterface::class,
            function ($app) {
                return match (
                    config('product-search.collector')
                ) {
                    'serpapi' => $app->make(
                        SerpApiCollector::class
                    ),

                    'fake' => $app->make(
                        FakeProductCollector::class
                    ),

                    'mercadolivre' => $app->make(
                        MercadoLivreCollector::class
                    ),

                    default => throw new RuntimeException(
                        'Collector de produtos inválido.'
                    ),
                };
            }
        );
    }

    public function boot(): void
    {
        //
    }
}