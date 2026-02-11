<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Infrastructure\Http\Controllers\ReservationController;

Route::prefix('reservations')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ReservationController::class, 'index']);
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::post('/{id}/confirm', [ReservationController::class, 'confirm']);
    Route::post('/{id}/check-in', [ReservationController::class, 'checkIn']);
    Route::post('/{id}/check-out', [ReservationController::class, 'checkOut']);
    Route::post('/{id}/cancel', [ReservationController::class, 'cancel']);
    Route::post('/{id}/special-requests', [ReservationController::class, 'addSpecialRequest']);
});
