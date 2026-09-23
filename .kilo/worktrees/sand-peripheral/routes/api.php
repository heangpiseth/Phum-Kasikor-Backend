<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\FirebaseAuthController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Farmer\FarmerController;
use App\Http\Controllers\Api\Farmer\ProductController as FarmerProductController;
use App\Http\Controllers\Api\Farmer\InventoryController;
use App\Http\Controllers\Api\Farmer\OrderController as FarmerOrderController;

// ========== PUBLIC AUTH ROUTES ==========
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::post('firebase/verify', [FirebaseAuthController::class, 'verifyToken']);
});

// ========== PROTECTED ROUTES ==========
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::put('choose-role', [ProfileController::class, 'chooseRole']);
        Route::put('setup', [ProfileController::class, 'setupProfile']);
        Route::put('location', [ProfileController::class, 'setupLocation']);
    });

    Route::prefix('customer')->group(function () {
        Route::get('favorites', [CustomerController::class, 'favorites']);
        Route::post('favorites', [CustomerController::class, 'storeFavorite']);
        Route::delete('favorites/{product}', [CustomerController::class, 'destroyFavorite']);

        Route::get('farm-favorites', [CustomerController::class, 'farmFavorites']);
        Route::post('farm-favorites', [CustomerController::class, 'storeFarmFavorite']);
        Route::delete('farm-favorites/{farm}', [CustomerController::class, 'destroyFarmFavorite']);

        Route::post('reviews', [CustomerController::class, 'storeReview']);
        Route::put('reviews/{review}', [CustomerController::class, 'updateReview']);
        Route::delete('reviews/{review}', [CustomerController::class, 'destroyReview']);

        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index']);
            Route::post('items', [CartController::class, 'addItem']);
            Route::put('items/{item}', [CartController::class, 'updateItem']);
            Route::delete('items/{item}', [CartController::class, 'removeItem']);
            Route::delete('/', [CartController::class, 'clear']);
        });

        Route::prefix('products')->group(function () {
            Route::get('categories', [CustomerProductController::class, 'categories']);
            Route::get('/', [CustomerProductController::class, 'index']);
            Route::get('{product}', [CustomerProductController::class, 'show']);
            Route::get('{product}/reviews', [CustomerProductController::class, 'reviews']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [CustomerOrderController::class, 'index']);
            Route::post('/', [CustomerOrderController::class, 'store']);
            Route::get('{order}', [CustomerOrderController::class, 'show']);
            Route::put('{order}/cancel', [CustomerOrderController::class, 'cancel']);
        });
    });

    Route::prefix('farmer')->group(function () {
        Route::prefix('farms')->group(function () {
            Route::get('/', [FarmerController::class, 'index']);
            Route::post('/', [FarmerController::class, 'store']);
            Route::get('{farm}', [FarmerController::class, 'show']);
            Route::put('{farm}', [FarmerController::class, 'update']);
            Route::delete('{farm}', [FarmerController::class, 'destroy']);

            Route::get('{farm}/fields', [FarmerController::class, 'fields']);
            Route::post('{farm}/fields', [FarmerController::class, 'storeField']);
            Route::put('{farm}/fields/{field}', [FarmerController::class, 'updateField']);
            Route::delete('{farm}/fields/{field}', [FarmerController::class, 'destroyField']);

            Route::get('{farm}/crops', [FarmerController::class, 'crops']);
            Route::post('{farm}/crops', [FarmerController::class, 'storeCrop']);
            Route::put('{farm}/crops/{crop}', [FarmerController::class, 'updateCrop']);
            Route::delete('{farm}/crops/{crop}', [FarmerController::class, 'destroyCrop']);

            Route::post('{farm}/crops/{crop}/watering-logs', [FarmerController::class, 'storeWateringLog']);
            Route::post('{farm}/crops/{crop}/harvest-logs', [FarmerController::class, 'storeHarvestLog']);
        });

        Route::prefix('farms/{farm}/products')->group(function () {
            Route::get('categories', [FarmerProductController::class, 'categories']);
            Route::post('categories', [FarmerProductController::class, 'storeCategory']);
            Route::get('/', [FarmerProductController::class, 'index']);
            Route::post('/', [FarmerProductController::class, 'store']);
            Route::get('{product}', [FarmerProductController::class, 'show']);
            Route::put('{product}', [FarmerProductController::class, 'update']);
            Route::delete('{product}', [FarmerProductController::class, 'destroy']);

            Route::post('{product}/images', [FarmerProductController::class, 'storeImage']);
            Route::delete('{product}/images/{image}', [FarmerProductController::class, 'destroyImage']);
        });

        Route::prefix('farms/{farm}/inventory')->group(function () {
            Route::get('categories', [InventoryController::class, 'categories']);
            Route::post('categories', [InventoryController::class, 'storeCategory']);
            Route::get('/', [InventoryController::class, 'index']);
            Route::post('/', [InventoryController::class, 'store']);
            Route::put('{item}', [InventoryController::class, 'update']);
            Route::delete('{item}', [InventoryController::class, 'destroy']);
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [FarmerOrderController::class, 'index']);
            Route::get('{order}', [FarmerOrderController::class, 'show']);
            Route::put('{order}', [FarmerOrderController::class, 'updateStatus']);
            Route::put('{order}/mark-paid', [FarmerOrderController::class, 'markPaymentPaid']);
        });
    });
});