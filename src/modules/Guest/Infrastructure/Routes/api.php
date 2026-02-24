<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Presentation\Http\Action\DeleteGuestProfileAction;
use Modules\Guest\Presentation\Http\Action\ListGuestProfilesAction;
use Modules\Guest\Presentation\Http\Action\ShowGuestProfileAction;
use Modules\Guest\Presentation\Http\Action\UpdateGuestProfileAction;

Route::prefix('guests')->middleware('auth:sanctum')->group(function () {
    Route::get('/', ListGuestProfilesAction::class);
    Route::get('/{uuid}', ShowGuestProfileAction::class);
    Route::put('/{uuid}', UpdateGuestProfileAction::class);
    Route::delete('/{uuid}', DeleteGuestProfileAction::class);
});
