<?php

use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\OfferManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureSubscriptionActive;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::view('/planos', 'pricing')->name('pricing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/cadastro', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/assinatura', [SubscriptionController::class, 'show'])->name('subscription.show');

    Route::middleware(EnsureSubscriptionActive::class)->prefix('app')->name('app.')->group(function () {
        Route::get('/', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::get('/buscar', [HomeController::class, 'search'])->name('search');
        Route::get('/produto/{product:slug}', [HomeController::class, 'show'])->name('products.show');
    });

    Route::middleware([EnsureSubscriptionActive::class, EnsureAdmin::class])->prefix('painel')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('produtos', ProductManagementController::class)->except('show');
        Route::resource('ofertas', OfferManagementController::class)->except('show');
        Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
        Route::post('/catalogo/categorias', [CatalogController::class, 'storeCategory'])->name('catalog.categories.store');
        Route::delete('/catalogo/categorias/{category}', [CatalogController::class, 'destroyCategory'])->name('catalog.categories.destroy');
        Route::post('/catalogo/marcas', [CatalogController::class, 'storeBrand'])->name('catalog.brands.store');
        Route::delete('/catalogo/marcas/{brand}', [CatalogController::class, 'destroyBrand'])->name('catalog.brands.destroy');
        Route::post('/catalogo/fontes', [CatalogController::class, 'storeSource'])->name('catalog.sources.store');
        Route::delete('/catalogo/fontes/{source}', [CatalogController::class, 'destroySource'])->name('catalog.sources.destroy');
    });
});

// IA
