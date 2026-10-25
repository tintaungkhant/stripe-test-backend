<?php

use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Customer\ProductController as CustomerProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('products', AdminProductController::class);
    });

    Route::middleware('auth:sanctum')->prefix('customer')->name('customer.')->group(function () {
        Route::apiResource('products', CustomerProductController::class)->only(['index', 'show']);
    });
});
