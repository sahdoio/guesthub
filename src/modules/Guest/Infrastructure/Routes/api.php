<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Presentation\Http\Action\DeleteGuestAction;
use Modules\Guest\Presentation\Http\Action\ListGuestsAction;
use Modules\Guest\Presentation\Http\Action\ShowGuestAction;
use Modules\Guest\Presentation\Http\Action\UpdateGuestAction;

Route::prefix('guests')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Admin and Superadmin: full access
    Route::middleware(['role:admin,superadmin'])->group(function () {
        Route::get('/', ListGuestsAction::class);
        Route::delete('/{uuid}', DeleteGuestAction::class);
    });

    // Guest can view/update own profile; Admin/Superadmin can access any
    Route::middleware(['role:guest,admin,superadmin'])->group(function () {
        Route::get('/{uuid}', ShowGuestAction::class);
        Route::put('/{uuid}', UpdateGuestAction::class);
    });
});
