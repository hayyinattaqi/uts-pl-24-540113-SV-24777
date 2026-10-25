<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('medicines', MedicineController::class);

    Route::post('/stock-in', [StockController::class, 'stockIn']);
    Route::post('/stock-out', [StockController::class, 'stockOut']);
    Route::get('/stock-movements', [StockController::class, 'movements']);
    Route::get('/dashboard', DashboardController::class);
});
