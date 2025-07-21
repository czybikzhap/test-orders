<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StockMovementController;


// Склады
Route::get('/warehouses', [WarehouseController::class, 'index']);

// Товары с остатками
Route::get('/products', [ProductController::class, 'index']);

// Заказы
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::put('/{order}', [OrderController::class, 'update']);
    Route::patch('/{order}/complete', [OrderController::class, 'complete']);
    Route::patch('/{order}/cancel', [OrderController::class, 'cancel']);
    Route::patch('/{order}/resume', [OrderController::class, 'resume']);
});

// Движения товаров
Route::prefix('stock-movements')->group(function () {
    Route::get('/', [StockMovementController::class, 'index']);
    Route::get('/statistics', [StockMovementController::class, 'statistics']);
});
