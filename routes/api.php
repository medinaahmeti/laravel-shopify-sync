<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

// TEMP sanity route
Route::get('/ping', fn () => response()->json(['ok' => true]));

// Protected routes
Route::middleware('api.token')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('api.products.show');
});
