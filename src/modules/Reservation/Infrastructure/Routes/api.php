<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Presentation\Http\Action\AddSpecialRequestAction;
use Modules\Reservation\Presentation\Http\Action\CancelReservationAction;
use Modules\Reservation\Presentation\Http\Action\CheckInAction;
use Modules\Reservation\Presentation\Http\Action\CheckOutAction;
use Modules\Reservation\Presentation\Http\Action\ConfirmReservationAction;
use Modules\Reservation\Presentation\Http\Action\CreateReservationAction;
use Modules\Reservation\Presentation\Http\Action\ListReservationsAction;
use Modules\Reservation\Presentation\Http\Action\ShowReservationAction;

Route::prefix('reservations')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Guest, Admin, Superadmin can access
    Route::middleware(['role:guest,admin,superadmin'])->group(function () {
        Route::get('/', ListReservationsAction::class);
        Route::post('/', CreateReservationAction::class);
        Route::get('/{id}', ShowReservationAction::class);
        Route::post('/{id}/cancel', CancelReservationAction::class);
        Route::post('/{id}/check-in', CheckInAction::class);
        Route::post('/{id}/special-requests', AddSpecialRequestAction::class);
    });

    // Admin and Superadmin only
    Route::middleware(['role:admin,superadmin'])->group(function () {
        Route::post('/{id}/confirm', ConfirmReservationAction::class);
        Route::post('/{id}/check-out', CheckOutAction::class);
    });
});
