<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['guest', 'throttle:auth'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendReset'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shipments. Static paths first so they are not captured by {shipment}.
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::get('/shipments/export', [ShipmentController::class, 'export'])->name('shipments.export');
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('/shipments/{shipment}/edit', [ShipmentController::class, 'edit'])->name('shipments.edit');
        Route::match(['put', 'patch'], '/shipments/{shipment}', [ShipmentController::class, 'update'])->name('shipments.update');
        Route::post('/shipments/{shipment}/tracking', [TrackingController::class, 'store'])->name('tracking.store');
        Route::post('/shipments/{shipment}/exceptions', [ExceptionController::class, 'store'])->name('exceptions.store');
        Route::patch('/exceptions/{exception}', [ExceptionController::class, 'update'])->name('exceptions.update');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/carriers', [CarrierController::class, 'index'])->name('carriers.index');
        Route::get('/transport', [TransportController::class, 'index'])->name('transport.index');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    });
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
    Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])->name('shipments.destroy'); // admin-only via policy

    Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::get('/tracking/data', [TrackingController::class, 'data'])->name('tracking.data');

    Route::get('/weather', [WeatherController::class, 'index'])->name('weather.index');
    Route::get('/weather/search', [WeatherController::class, 'search'])->name('weather.search');
    Route::get('/weather/data', [WeatherController::class, 'data'])->name('weather.data');
    Route::get('/currency', [CurrencyController::class, 'index'])->name('currency.index');
    Route::get('/currency/convert', [CurrencyController::class, 'convert'])->name('currency.convert');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::get('/exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::middleware('role:admin')->group(function () {
        Route::post('/carriers', [CarrierController::class, 'store'])->name('carriers.store');
        Route::get('/carriers/{carrier}/edit', [CarrierController::class, 'edit'])->name('carriers.edit');
        Route::put('/carriers/{carrier}', [CarrierController::class, 'update'])->name('carriers.update');
        Route::delete('/carriers/{carrier}', [CarrierController::class, 'destroy'])->name('carriers.destroy');

        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])->name('locations.edit');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
