<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Infrastructure\Http\View\UserCreateView;
use Modules\User\Infrastructure\Http\View\UserDeleteView;
use Modules\User\Infrastructure\Http\View\UserEditView;
use Modules\User\Infrastructure\Http\View\UserListView;
use Modules\User\Infrastructure\Http\View\UserShowView;
use Modules\User\Infrastructure\Http\View\UserStoreView;
use Modules\User\Infrastructure\Http\View\UserUpdateView;

Route::middleware(['auth', 'owner'])->prefix('guests')->group(function () {
    Route::get('/', UserListView::class)->name('guests.index');
    Route::get('/create', UserCreateView::class)->name('guests.create');
    Route::post('/', UserStoreView::class)->name('guests.store');
    Route::get('/{id}', UserShowView::class)->name('guests.show');
    Route::get('/{id}/edit', UserEditView::class)->name('guests.edit');
    Route::put('/{id}', UserUpdateView::class)->name('guests.update');
    Route::delete('/{id}', UserDeleteView::class)->name('guests.delete');
});
