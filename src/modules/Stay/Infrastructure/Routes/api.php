<?php

use Illuminate\Support\Facades\Route;
use Modules\Stay\Presentation\Http\Action\AddSpecialRequestAction;
use Modules\Stay\Presentation\Http\Action\CancelReservationAction;
use Modules\Stay\Presentation\Http\Action\CheckInAction;
use Modules\Stay\Presentation\Http\Action\CheckOutAction;
use Modules\Stay\Presentation\Http\Action\ConfirmReservationAction;
use Modules\Stay\Presentation\Http\Action\CreateReservationAction;
use Modules\Stay\Presentation\Http\Action\ListReservationsAction;
use Modules\Stay\Presentation\Http\Action\ShowReservationAction;

Route::prefix('stays')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    //
});

Route::prefix('reservations')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Guest, Owner, Superadmin can access
    Route::middleware(['type:guest,owner,superadmin'])->group(function () {
        Route::get('/', ListReservationsAction::class);
        Route::post('/', CreateReservationAction::class);
        Route::get('/{id}', ShowReservationAction::class);
        Route::post('/{id}/cancel', CancelReservationAction::class);
        Route::post('/{id}/check-in', CheckInAction::class);
        Route::post('/{id}/special-requests', AddSpecialRequestAction::class);
    });

    // Owner and Superadmin only
    Route::middleware(['type:owner,superadmin'])->group(function () {
        Route::post('/{id}/confirm', ConfirmReservationAction::class);
        Route::post('/{id}/check-out', CheckOutAction::class);
    });
});
