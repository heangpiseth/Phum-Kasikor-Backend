<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================================
// LOCATION
// ============================================================

use App\Http\Controllers\Api\LocationController;

// ============================================================
// AUTH
// ============================================================

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\FirebaseAuthController;
use App\Http\Controllers\Api\Auth\ProfileController;

// ============================================================
// CUSTOMER
// ============================================================

use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;

// ============================================================
// FARMER
// ============================================================

use App\Http\Controllers\Api\Farmer\AiChatController;
use App\Http\Controllers\Api\Farmer\HarvestController;
use App\Http\Controllers\Api\Farmer\FarmerController;
use App\Http\Controllers\Api\Farmer\FarmerVerificationController;
use App\Http\Controllers\Api\Farmer\ProductController as FarmerProductController;
use App\Http\Controllers\Api\Farmer\InventoryController;
use App\Http\Controllers\Api\Farmer\OrderController as FarmerOrderController;
use App\Http\Controllers\Api\Farmer\WeatherController;
use App\Http\Controllers\Api\Farmer\WateringRecommendationController;


// ============================================================
// PUBLIC ROUTES
// ============================================================

Route::get('/locations', [
    LocationController::class,
    'index',
]);


// ============================================================
// AUTH
// ============================================================

Route::prefix('auth')->group(function () {

    // Register
    Route::post('/register', [
        AuthController::class,
        'register',
    ]);

    // Login
    Route::post('/login', [
        AuthController::class,
        'login',
    ]);

    // Verify account
    Route::post('/verify', [
        AuthController::class,
        'verify',
    ]);

    // Firebase authentication
    Route::post('/firebase/verify', [
        FirebaseAuthController::class,
        'verify',
    ]);
});


// ============================================================
// PROTECTED ROUTES
// ============================================================

Route::middleware('auth:sanctum')->group(function () {

    // ========================================================
    // AUTH / USER
    // ========================================================

    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    });

    Route::post('/logout', [
        AuthController::class,
        'logout',
    ]);

    // Profile
    Route::prefix('profile')->group(function () {

        Route::get('/', [
            ProfileController::class,
            'show',
        ]);

        Route::put('/', [
            ProfileController::class,
            'update',
        ]);

        Route::post('/image', [
            ProfileController::class,
            'updateImage',
        ]);
    });


    // ========================================================
    // CUSTOMER
    // ========================================================

    Route::prefix('customer')->group(function () {

        // ----------------------------------------------------
        // CUSTOMER HOME
        // ----------------------------------------------------

        Route::get('/', [
            CustomerController::class,
            'index',
        ]);


        // ----------------------------------------------------
        // CATEGORIES
        // ----------------------------------------------------

        Route::get('/categories', [
            CustomerProductController::class,
            'categories',
        ]);


        // ----------------------------------------------------
        // PRODUCTS
        // ----------------------------------------------------

        Route::get('/products', [
            CustomerProductController::class,
            'index',
        ]);

        Route::get('/products/{product}', [
            CustomerProductController::class,
            'show',
        ]);


        // ----------------------------------------------------
        // CART
        // ----------------------------------------------------

        Route::get('/cart', [
            CartController::class,
            'index',
        ]);

        Route::post('/cart/items', [
            CartController::class,
            'addItem',
        ]);

        Route::put('/cart/items/{item}', [
            CartController::class,
            'updateItem',
        ]);

        Route::delete('/cart/items/{item}', [
            CartController::class,
            'removeItem',
        ]);

        Route::delete('/cart', [
            CartController::class,
            'clear',
        ]);


        // ----------------------------------------------------
        // ORDERS
        // ----------------------------------------------------

        Route::get('/orders', [
            CustomerOrderController::class,
            'index',
        ]);

        Route::post('/orders', [
            CustomerOrderController::class,
            'store',
        ]);

        Route::get('/orders/{order}', [
            CustomerOrderController::class,
            'show',
        ]);

        Route::post('/orders/{order}/cancel', [
            CustomerOrderController::class,
            'cancel',
        ]);


        // ----------------------------------------------------
        // FAVORITES
        // ----------------------------------------------------

        Route::get('/favorites', [
            CustomerController::class,
            'favorites',
        ]);

        Route::post('/favorites/{product}', [
            CustomerController::class,
            'toggleFavorite',
        ]);


        // ----------------------------------------------------
        // FARM FAVORITES
        // ----------------------------------------------------

        Route::get('/farm-favorites', [
            CustomerController::class,
            'farmFavorites',
        ]);

        Route::post('/farm-favorites/{farm}', [
            CustomerController::class,
            'toggleFarmFavorite',
        ]);
    });


    // ========================================================
    // FARMER
    // ========================================================

    Route::prefix('farmer')->group(function () {

        // ====================================================
        // AI AGRICULTURE ASSISTANT
        // ====================================================

        Route::post('/ai/chat', [
            AiChatController::class,
            'chat',
        ]);


        // ====================================================
        // WEATHER
        // ====================================================

        Route::get('/weather', [
            WeatherController::class,
            'index',
        ]);


        // ====================================================
        // WATERING RECOMMENDATION
        // ====================================================

        Route::get('/watering-recommendation', [
            WateringRecommendationController::class,
            'index',
        ]);


        // ====================================================
        // FARMER VERIFICATION
        // ====================================================

        Route::prefix('verification')->group(function () {

            Route::get('/', [
                FarmerVerificationController::class,
                'show',
            ]);

            Route::post('/', [
                FarmerVerificationController::class,
                'store',
            ]);

            Route::delete('/verification', [
                FarmerVerificationController::class,
                'destroy',
            ]);
        });


        // ====================================================
        // FARM
        // ====================================================

        Route::get('/farm', [
            FarmerController::class,
            'showFarm',
        ]);

        Route::post('/farm', [
            FarmerController::class,
            'storeFarm',
        ]);

        Route::put('/farm', [
            FarmerController::class,
            'updateFarm',
        ]);

        Route::delete('/farm', [
            FarmerController::class,
            'destroyFarm',
        ]);


        // ====================================================
        // FIELDS
        // ====================================================

        Route::get('/fields', [
            FarmerController::class,
            'fields',
        ]);

        Route::post('/fields', [
            FarmerController::class,
            'storeField',
        ]);

        Route::get('/fields/{field}', [
            FarmerController::class,
            'showField',
        ]);

        Route::put('/fields/{field}', [
            FarmerController::class,
            'updateField',
        ]);

        Route::delete('/fields/{field}', [
            FarmerController::class,
            'destroyField',
        ]);


        // ====================================================
        // CROPS
        // ====================================================

        Route::get('/crops', [
            FarmerController::class,
            'crops',
        ]);

        Route::post('/crops', [
            FarmerController::class,
            'storeCrop',
        ]);

        Route::get('/crops/{crop}', [
            FarmerController::class,
            'showCrop',
        ]);

        Route::put('/crops/{crop}', [
            FarmerController::class,
            'updateCrop',
        ]);

        Route::delete('/crops/{crop}', [
            FarmerController::class,
            'destroyCrop',
        ]);


        // ====================================================
        // WATERING LOGS
        // ====================================================

        Route::get('/watering-logs', [
            FarmerController::class,
            'wateringLogs',
        ]);

        Route::post('/watering-logs', [
            FarmerController::class,
            'storeWateringLog',
        ]);

        Route::get('/watering-logs/{wateringLog}', [
            FarmerController::class,
            'showWateringLog',
        ]);

        Route::put('/watering-logs/{wateringLog}', [
            FarmerController::class,
            'updateWateringLog',
        ]);

        Route::delete('/watering-logs/{wateringLog}', [
            FarmerController::class,
            'destroyWateringLog',
        ]);


        // ====================================================
        // HARVEST
        // ====================================================

        Route::get('/harvests', [
            HarvestController::class,
            'index',
        ]);

        Route::post('/harvests', [
            HarvestController::class,
            'store',
        ]);

        Route::get('/harvests/{harvest}', [
            HarvestController::class,
            'show',
        ]);

        Route::put('/harvests/{harvest}', [
            HarvestController::class,
            'update',
        ]);

        Route::delete('/harvests/{harvest}', [
            HarvestController::class,
            'destroy',
        ]);


        // ====================================================
        // FARMER PRODUCTS
        // ====================================================

        Route::get('/products', [
            FarmerProductController::class,
            'index',
        ]);

        Route::post('/products', [
            FarmerProductController::class,
            'store',
        ]);

        Route::get('/products/{product}', [
            FarmerProductController::class,
            'show',
        ]);

        Route::put('/products/{product}', [
            FarmerProductController::class,
            'update',
        ]);

        Route::delete('/products/{product}', [
            FarmerProductController::class,
            'destroy',
        ]);


        // ====================================================
        // INVENTORY
        // ====================================================

        Route::get('/inventory', [
            InventoryController::class,
            'index',
        ]);

        Route::post('/inventory', [
            InventoryController::class,
            'store',
        ]);

        Route::get('/inventory/{inventory}', [
            InventoryController::class,
            'show',
        ]);

        Route::put('/inventory/{inventory}', [
            InventoryController::class,
            'update',
        ]);

        Route::delete('/inventory/{inventory}', [
            InventoryController::class,
            'destroy',
        ]);


        // ====================================================
        // EARNINGS
        // ====================================================

        Route::get('/earnings', [
            FarmerController::class,
            'earnings',
        ]);


        // ====================================================
        // FARMER ORDERS
        // ====================================================

        Route::get('/orders', [
            FarmerOrderController::class,
            'index',
        ]);

        Route::get('/orders/{order}', [
            FarmerOrderController::class,
            'show',
        ]);

        Route::put('/orders/{order}/status', [
            FarmerOrderController::class,
            'updateStatus',
        ]);
    });
});