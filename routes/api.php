<?php
use App\Http\Controllers\AiController;
use App\Http\Controllers\ExternalProductSearchController;
use Illuminate\Support\Facades\Route;

Route::post('/ia/analisar', [
    AiController::class,
    'analisar',
])->middleware('throttle:10,1');