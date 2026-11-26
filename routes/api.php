<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [Api\AuthController::class, 'register']);
    Route::post('/login', [Api\AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/logout', [Api\AuthController::class, 'logout']);
    Route::get('/user', [Api\AuthController::class, 'user']);
    Route::get('/dashboard', Api\DashboardController::class);

    // Shipments (authorization is enforced by policies / form requests)
    Route::apiResource('shipments', Api\ShipmentController::class);
    Route::get('/shipments/{shipment}/tracking', [Api\TrackingController::class, 'index']);
    Route::post('/shipments/{shipment}/tracking', [Api\TrackingController::class, 'store']);
    Route::get('/shipments/{shipment}/exceptions', [Api\ExceptionController::class, 'index']);
    Route::post('/shipments/{shipment}/exceptions', [Api\ExceptionController::class, 'store']);
    Route::patch('/exceptions/{exception}', [Api\ExceptionController::class, 'update']);

    // Reference data: everyone can read, only admins can create
    Route::get('/carriers', [Api\CarrierController::class, 'index']);
    Route::get('/locations', [Api\LocationController::class, 'index']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/carriers', [Api\CarrierController::class, 'store']);
        Route::post('/locations', [Api\LocationController::class, 'store']);
    });
});
});
