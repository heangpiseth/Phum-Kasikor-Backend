<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// LOCATION
use App\Http\Controllers\Api\LocationController;

// AUTH
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\FirebaseAuthController;
use App\Http\Controllers\Api\Auth\ProfileController;

// CUSTOMER
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;

// FARMER
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
// LOCATION
// ============================================================

Route::get('/locations', [
    LocationController::class,
    'index'
]);

Route::post('/location/reverse-geocode', [
    LocationController::class,
    'reverseGeocode'
]);


// ============================================================
// AUTH
// ============================================================

Route::prefix('auth')->group(function () {

    Route::post('/register', [
        AuthController::class,
        'register'
    ]);

    Route::post('/login', [
        AuthController::class,
        'login'
    ]);

    Route::post('/verify', [
        AuthController::class,
        'verify'
    ]);

    Route::post('/firebase/verify', [
        FirebaseAuthController::class,
        'verify'
    ]);
});


// ============================================================
// PROTECTED
// ============================================================

Route::middleware('auth:sanctum')->group(function () {

    // ========================================================
    // CURRENT USER
    // ========================================================

    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    });

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);


    // ========================================================
    // PROFILE
    // ========================================================

    Route::prefix('profile')->group(function () {

        Route::get('/', [
            ProfileController::class,
            'show'
        ]);

        Route::put('/', [
            ProfileController::class,
            'update'
        ]);

        Route::post('/image', [
            ProfileController::class,
            'updateImage'
        ]);

        Route::put('/choose-role', [
            ProfileController::class,
            'chooseRole'
        ]);

        Route::put('/location', [
            ProfileController::class,
            'setupLocation'
        ]);

        Route::put('/setup', [
            ProfileController::class,
            'setupProfile'
        ]);
    });


    // ========================================================
    // CUSTOMER
    // ========================================================

    Route::prefix('customer')->group(function () {

        Route::get('/', [
            CustomerController::class,
            'index'
        ]);

        Route::get('/categories', [
            CustomerProductController::class,
            'categories'
        ]);

        Route::get('/products', [
            CustomerProductController::class,
            'index'
        ]);

        Route::get('/products/{product}', [
            CustomerProductController::class,
            'show'
        ]);


        // CART

        Route::get('/cart', [
            CartController::class,
            'index'
        ]);

        Route::post('/cart/items', [
            CartController::class,
            'addItem'
        ]);

        Route::put('/cart/items/{item}', [
            CartController::class,
            'updateItem'
        ]);

        Route::delete('/cart/items/{item}', [
            CartController::class,
            'removeItem'
        ]);

        Route::delete('/cart', [
            CartController::class,
            'clear'
        ]);


        // ORDERS

        Route::get('/orders', [
            CustomerOrderController::class,
            'index'
        ]);

        Route::post('/orders', [
            CustomerOrderController::class,
            'store'
        ]);

        Route::get('/orders/{order}', [
            CustomerOrderController::class,
            'show'
        ]);

        Route::post('/orders/{order}/cancel', [
            CustomerOrderController::class,
            'cancel'
        ]);


        // FAVORITES

        Route::get('/favorites', [
            CustomerController::class,
            'favorites'
        ]);

        Route::post('/favorites/{product}', [
            CustomerController::class,
            'toggleFavorite'
        ]);

        Route::get('/farm-favorites', [
            CustomerController::class,
            'farmFavorites'
        ]);

        Route::post('/farm-favorites/{farm}', [
            CustomerController::class,
            'toggleFarmFavorite'
        ]);
    });


    // ========================================================
    // FARMER
    // ========================================================

    Route::prefix('farmer')->group(function () {

        // ----------------------------------------------------
        // AI
        // ----------------------------------------------------

        Route::post('/ai/chat', [
            AiChatController::class,
            'chat'
        ]);


        // ----------------------------------------------------
        // WEATHER
        // ----------------------------------------------------

        Route::get('/weather', [
            WeatherController::class,
            'index'
        ]);


        // ----------------------------------------------------
        // WATERING RECOMMENDATION
        // ----------------------------------------------------

        Route::get('/watering-recommendation', [
            WateringRecommendationController::class,
            'index'
        ]);

        Route::get('/watering-recommendation/{crop}', [
            WateringRecommendationController::class,
            'show'
        ]);


        // ----------------------------------------------------
        // FARMER VERIFICATION
        // ----------------------------------------------------

        Route::prefix('verification')->group(function () {

            Route::get('/', [
                FarmerVerificationController::class,
                'show'
            ]);

            Route::post('/', [
                FarmerVerificationController::class,
                'store'
            ]);

            Route::delete('/', [
                FarmerVerificationController::class,
                'destroy'
            ]);
        });


        // ====================================================
        // FARMS
        // ====================================================

        Route::get('/farms', [
            FarmerController::class,
            'index'
        ]);

        Route::post('/farms', [
            FarmerController::class,
            'store'
        ]);

        Route::get('/farms/{farm}', [
            FarmerController::class,
            'show'
        ]);

        Route::put('/farms/{farm}', [
            FarmerController::class,
            'update'
        ]);

        Route::delete('/farms/{farm}', [
            FarmerController::class,
            'destroy'
        ]);


        // ====================================================
        // FIELDS
        // ====================================================

        Route::get('/farms/{farm}/fields', [
            FarmerController::class,
            'fields'
        ]);

        Route::post('/farms/{farm}/fields', [
            FarmerController::class,
            'storeField'
        ]);

        Route::get('/farms/{farm}/fields/{field}', [
            FarmerController::class,
            'showField'
        ]);

        Route::put('/farms/{farm}/fields/{field}', [
            FarmerController::class,
            'updateField'
        ]);

        Route::delete('/farms/{farm}/fields/{field}', [
            FarmerController::class,
            'destroyField'
        ]);


        // ====================================================
        // CROPS
        // ====================================================

        Route::get('/farms/{farm}/crops', [
            FarmerController::class,
            'crops'
        ]);

        Route::post('/farms/{farm}/crops', [
            FarmerController::class,
            'storeCrop'
        ]);

        Route::get('/farms/{farm}/crops/{crop}', [
            FarmerController::class,
            'showCrop'
        ]);

        Route::put('/farms/{farm}/crops/{crop}', [
            FarmerController::class,
            'updateCrop'
        ]);

        Route::delete('/farms/{farm}/crops/{crop}', [
            FarmerController::class,
            'destroyCrop'
        ]);


        // ====================================================
        // WATERING LOGS
        // ====================================================

        Route::get('/watering-logs', [
            FarmerController::class,
            'wateringLogs'
        ]);

        Route::post('/watering-logs', [
            FarmerController::class,
            'storeWateringLog'
        ]);

        Route::get('/watering-logs/{wateringLog}', [
            FarmerController::class,
            'showWateringLog'
        ]);

        Route::put('/watering-logs/{wateringLog}', [
            FarmerController::class,
            'updateWateringLog'
        ]);

        Route::delete('/watering-logs/{wateringLog}', [
            FarmerController::class,
            'destroyWateringLog'
        ]);


        // ====================================================
        // HARVESTS
        // ====================================================

        Route::get('/harvests', [
            HarvestController::class,
            'index'
        ]);

        Route::post('/harvests', [
            HarvestController::class,
            'store'
        ]);

        Route::get('/harvests/{harvest}', [
            HarvestController::class,
            'show'
        ]);

        Route::put('/harvests/{harvest}', [
            HarvestController::class,
            'update'
        ]);

        Route::delete('/harvests/{harvest}', [
            HarvestController::class,
            'destroy'
        ]);


        // ====================================================
        // PRODUCTS
        // ====================================================

        Route::get('/farms/{farm}/products', [
            FarmerProductController::class,
            'index'
        ]);

        Route::post('/farms/{farm}/products', [
            FarmerProductController::class,
            'store'
        ]);

        Route::get('/farms/{farm}/products/{product}', [
            FarmerProductController::class,
            'show'
        ]);

        Route::put('/farms/{farm}/products/{product}', [
            FarmerProductController::class,
            'update'
        ]);

        Route::delete('/farms/{farm}/products/{product}', [
            FarmerProductController::class,
            'destroy'
        ]);


        // ====================================================
        // PRODUCT IMAGES
        // ====================================================

        Route::post(
            '/farms/{farm}/products/{product}/images',
            [
                FarmerProductController::class,
                'storeImage'
            ]
        );

        Route::delete(
            '/farms/{farm}/products/{product}/images/{image}',
            [
                FarmerProductController::class,
                'destroyImage'
            ]
        );

        Route::put(
            '/farms/{farm}/products/{product}/images/{image}/primary',
            [
                FarmerProductController::class,
                'setPrimaryImage'
            ]
        );


        // ====================================================
        // INVENTORY CATEGORIES
        // ====================================================

        Route::get(
            '/farms/{farm}/inventory/categories',
            [
                InventoryController::class,
                'categories'
            ]
        );

        Route::post(
            '/farms/{farm}/inventory/categories',
            [
                InventoryController::class,
                'storeCategory'
            ]
        );


        // ====================================================
        // INVENTORY
        // ====================================================

        Route::get(
            '/farms/{farm}/inventory',
            [
                InventoryController::class,
                'index'
            ]
        );

        Route::post(
            '/farms/{farm}/inventory',
            [
                InventoryController::class,
                'store'
            ]
        );

        Route::put(
            '/farms/{farm}/inventory/{item}',
            [
                InventoryController::class,
                'update'
            ]
        );

        Route::delete(
            '/farms/{farm}/inventory/{item}',
            [
                InventoryController::class,
                'destroy'
            ]
        );


        // ====================================================
        // EARNINGS
        // ====================================================

        Route::get('/earnings', [
            FarmerController::class,
            'earnings'
        ]);


        // ====================================================
        // ORDERS
        // ====================================================

        Route::get('/orders', [
            FarmerOrderController::class,
            'index'
        ]);

        Route::get('/orders/{order}', [
            FarmerOrderController::class,
            'show'
        ]);

        Route::put('/orders/{order}/status', [
            FarmerOrderController::class,
            'updateStatus'
        ]);
    });
});