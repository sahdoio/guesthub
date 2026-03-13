<?php

use Illuminate\Support\Facades\Route;
use Modules\Guest\Infrastructure\Http\View\GuestCreateView;
use Modules\Guest\Infrastructure\Http\View\GuestDeleteView;
use Modules\Guest\Infrastructure\Http\View\GuestEditView;
use Modules\Guest\Infrastructure\Http\View\GuestListView;
use Modules\Guest\Infrastructure\Http\View\GuestShowView;
use Modules\Guest\Infrastructure\Http\View\GuestStoreView;
use Modules\Guest\Infrastructure\Http\View\GuestUpdateView;

Route::middleware('auth')->prefix('guests')->group(function () {
    Route::get('/', GuestListView::class)->name('guests.index');
    Route::get('/create', GuestCreateView::class)->name('guests.create');
    Route::post('/', GuestStoreView::class)->name('guests.store');
    Route::get('/{id}', GuestShowView::class)->name('guests.show');
    Route::get('/{id}/edit', GuestEditView::class)->name('guests.edit');
    Route::put('/{id}', GuestUpdateView::class)->name('guests.update');
    Route::delete('/{id}', GuestDeleteView::class)->name('guests.delete');
});
