<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Infrastructure\Http\View\RoomCreateView;
use Modules\Inventory\Infrastructure\Http\View\RoomDeleteView;
use Modules\Inventory\Infrastructure\Http\View\RoomEditView;
use Modules\Inventory\Infrastructure\Http\View\RoomListView;
use Modules\Inventory\Infrastructure\Http\View\RoomShowView;
use Modules\Inventory\Infrastructure\Http\View\RoomStatusView;
use Modules\Inventory\Infrastructure\Http\View\RoomStoreView;
use Modules\Inventory\Infrastructure\Http\View\RoomUpdateView;

Route::middleware(['auth', 'owner'])->prefix('hotels/{slug}/rooms')->group(function () {
    Route::get('/', RoomListView::class)->name('rooms.index');
    Route::get('/create', RoomCreateView::class)->name('rooms.create');
    Route::post('/', RoomStoreView::class)->name('rooms.store');
    Route::get('/{id}', RoomShowView::class)->name('rooms.show');
    Route::get('/{id}/edit', RoomEditView::class)->name('rooms.edit');
    Route::put('/{id}', RoomUpdateView::class)->name('rooms.update');
    Route::post('/{id}/status', RoomStatusView::class)->name('rooms.status');
    Route::delete('/{id}', RoomDeleteView::class)->name('rooms.delete');
});
