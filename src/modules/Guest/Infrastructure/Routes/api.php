<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Infrastructure\Http\Controllers\GuestProfileController;

Route::prefix('guests')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [GuestProfileController::class, 'index']);
    Route::post('/', [GuestProfileController::class, 'store']);
    Route::get('/{uuid}', [GuestProfileController::class, 'show']);
    Route::put('/{uuid}', [GuestProfileController::class, 'update']);
    Route::delete('/{uuid}', [GuestProfileController::class, 'destroy']);
});
