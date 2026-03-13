<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Presentation\Http\Action\ChangeRoomStatusAction;
use Modules\Inventory\Presentation\Http\Action\DeleteRoomAction;
use Modules\Inventory\Presentation\Http\Action\ListRoomsAction;
use Modules\Inventory\Presentation\Http\Action\ShowRoomAction;
use Modules\Inventory\Presentation\Http\Action\UpdateRoomAction;

Route::prefix('rooms')->middleware(['auth:sanctum', 'tenant', 'role:admin,superadmin'])->group(function () {
    Route::get('/', ListRoomsAction::class);
    Route::get('/{uuid}', ShowRoomAction::class);
    Route::put('/{uuid}', UpdateRoomAction::class);
    Route::patch('/{uuid}/status', ChangeRoomStatusAction::class);
    Route::delete('/{uuid}', DeleteRoomAction::class);
});
