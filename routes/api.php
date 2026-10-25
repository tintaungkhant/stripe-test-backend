<?php

use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Customer\CartController;
use App\Http\Controllers\Api\V1\Customer\OrderController as CustomerOrderController;
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
        Route::post('products/image-upload-url', [AdminProductController::class, 'imageUploadUrl'])->name('products.image-upload-url');
        Route::apiResource('products', AdminProductController::class);
        Route::apiResource('orders', AdminOrderController::class)->only(['index', 'show']);
    });

    Route::middleware('auth:sanctum')->prefix('customer')->name('customer.')->group(function () {
        Route::apiResource('products', CustomerProductController::class)->only(['index', 'show']);
        Route::apiResource('orders', CustomerOrderController::class)->only(['index', 'store', 'show']);

        Route::get('cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('cart/items', [CartController::class, 'store'])->name('cart.items.store');
        Route::put('cart/items/{product}', [CartController::class, 'update'])->name('cart.items.update');
        Route::delete('cart/items/{product}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    });
});
