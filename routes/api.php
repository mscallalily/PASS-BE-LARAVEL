<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\ParkingOccupancyController;
use App\Http\Controllers\Api\AuditLogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VehicleManagementController;
use App\Http\Controllers\Api\DriverLicenseController;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

// Public password recovery routes
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/admin/vehicles', [VehicleManagementController::class, 'index']);
    Route::prefix('admin')->group(function () {
    Route::get(
        '/driver-licenses',
        [DriverLicenseController::class, 'index']
    );

    Route::patch(
        '/driver-licenses/{id}/review',
        [DriverLicenseController::class, 'review']
    );
});

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/parking/occupancy', [ParkingOccupancyController::class, 'index']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);

        Route::patch(
            '/users/{id}/approve',
            [UserManagementController::class, 'approve']
        );

        Route::patch(
            '/users/{id}/reject',
            [UserManagementController::class, 'reject']
        );
    });
});
