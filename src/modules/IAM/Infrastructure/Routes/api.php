<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Presentation\Http\Action\LoginAction;
use Modules\IAM\Presentation\Http\Action\LogoutAction;
use Modules\IAM\Presentation\Http\Action\RegisterAction;

Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterAction::class);
    Route::post('/login', LoginAction::class);
    Route::post('/logout', LogoutAction::class)->middleware('auth:sanctum');
});
