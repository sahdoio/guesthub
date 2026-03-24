<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Infrastructure\Http\Action\DeleteUserAction;
use Modules\IAM\Infrastructure\Http\Action\ListUsersAction;
use Modules\IAM\Infrastructure\Http\Action\LoginAction;
use Modules\IAM\Infrastructure\Http\Action\LogoutAction;
use Modules\IAM\Infrastructure\Http\Action\RegisterAction;
use Modules\IAM\Infrastructure\Http\Action\ShowUserAction;
use Modules\IAM\Infrastructure\Http\Action\UpdateUserAction;

Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterAction::class);
    Route::post('/login', LoginAction::class);
    Route::post('/logout', LogoutAction::class)->middleware('auth:sanctum');
});

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
