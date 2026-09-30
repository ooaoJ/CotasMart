<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\CollectorTestController;
use Illuminate\Support\Facades\Route;

Route::post('/ia/analisar', [
    AiController::class,
    'analisar',
])->middleware('throttle:10,1');

Route::post('/collectors/search', [
    CollectorTestController::class,
    'search',
])->middleware('throttle:5,1');