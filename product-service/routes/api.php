<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PRODUCT SERVICE — Port 8002
|--------------------------------------------------------------------------
|
| Handles: CRUD Product, Check Stock, Reduce Stock
| Auth diverifikasi lewat User Service (port 8001)
|
*/

// Public — list & detail products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Protected — manage products (auth dicek via User Service)
Route::post('/products', [ProductController::class, 'store']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

// Internal endpoints — dipanggil oleh Order Service
Route::post('/products/{id}/check-stock', [ProductController::class, 'checkStock']);
Route::post('/products/{id}/reduce-stock', [ProductController::class, 'reduceStock']);
Route::post('/products/{id}/restore-stock', [ProductController::class, 'restoreStock']);
