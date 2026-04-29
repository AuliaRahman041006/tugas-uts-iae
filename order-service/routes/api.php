<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ORDER SERVICE — Port 8003
|--------------------------------------------------------------------------
|
| ALUR SISTEM:
| 1. User melakukan order → POST /api/orders
| 2. Sistem mengecek user → panggil User Service (port 8001)
| 3. Sistem mengecek produk & stok → panggil Product Service (port 8002)
| 4. Order dibuat → simpan di database Order Service
| 5. Stok produk berkurang → panggil Product Service reduce-stock
|
*/

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
