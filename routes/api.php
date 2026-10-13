
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Support\Facades\Route;

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

    Route::prefix('admin')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);

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
