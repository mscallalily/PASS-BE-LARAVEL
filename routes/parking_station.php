<?php

use App\Http\Controllers\Api\ParkingStationController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:240,1')->prefix('devices/parking-station')->group(function () {
    Route::post('start', [ParkingStationController::class, 'start']);
    Route::post('poll', [ParkingStationController::class, 'poll']);
    Route::post('observe', [ParkingStationController::class, 'observe']);
});
Route::middleware(['auth:sanctum', \App\Http\Middleware\AccountAccess::class])->group(function () {
    Route::get('parking/slot-stations', [ParkingStationController::class, 'index']);
    Route::post('parking/slot-stations', [ParkingStationController::class, 'bind'])->middleware('throttle:20,1');
});
