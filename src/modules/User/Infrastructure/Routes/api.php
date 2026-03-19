<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Presentation\Http\Action\DeleteUserAction;
use Modules\User\Presentation\Http\Action\ListUsersAction;
use Modules\User\Presentation\Http\Action\ShowUserAction;
use Modules\User\Presentation\Http\Action\UpdateUserAction;

Route::prefix('guests')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Owner and Superadmin: full access
    Route::middleware(['type:owner,superadmin'])->group(function () {
        Route::get('/', ListUsersAction::class);
        Route::delete('/{uuid}', DeleteUserAction::class);
    });

    // Guest can view/update own profile; Owner/Superadmin can access any
    Route::middleware(['type:guest,owner,superadmin'])->group(function () {
        Route::get('/{uuid}', ShowUserAction::class);
        Route::put('/{uuid}', UpdateUserAction::class);
    });
});
