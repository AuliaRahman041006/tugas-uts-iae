<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| USER SERVICE — Port 8001
|--------------------------------------------------------------------------
|
| Handles: Registration, Login, Logout, Profile, Token Verification
|
*/

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});

// Internal endpoint — untuk verifikasi token dari service lain
Route::middleware('auth:sanctum')->get('/user/verify', [AuthController::class, 'verify']);
