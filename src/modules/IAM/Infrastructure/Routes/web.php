<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Infrastructure\Http\View\ImpersonateView;
use Modules\IAM\Infrastructure\Http\View\LoginSubmitView;
use Modules\IAM\Infrastructure\Http\View\ProfileEditView;
use Modules\IAM\Infrastructure\Http\View\ProfileShowView;
use Modules\IAM\Infrastructure\Http\View\ProfileUpdateView;
use Modules\IAM\Infrastructure\Http\View\LoginView;
use Modules\IAM\Infrastructure\Http\View\LogoutSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterHotelSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterHotelView;
use Modules\IAM\Infrastructure\Http\View\RegisterSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterView;
use Modules\IAM\Infrastructure\Http\View\StopImpersonationView;
use Modules\IAM\Infrastructure\Http\View\UserCreateView;
use Modules\IAM\Infrastructure\Http\View\UserDeleteView;
use Modules\IAM\Infrastructure\Http\View\UserEditView;
use Modules\IAM\Infrastructure\Http\View\UserListView;
use Modules\IAM\Infrastructure\Http\View\UserShowView;
use Modules\IAM\Infrastructure\Http\View\UserStoreView;
use Modules\IAM\Infrastructure\Http\View\UserUpdateView;

Route::middleware('guest')->group(function () {
    Route::get('/login', LoginView::class)->name('login');
    Route::post('/login', LoginSubmitView::class);
    Route::get('/register', RegisterView::class)->name('register');
    Route::post('/register', RegisterSubmitView::class);
    Route::get('/register/hotel', RegisterHotelView::class)->name('register.hotel');
    Route::post('/register/hotel', RegisterHotelSubmitView::class);
});

Route::post('/logout', LogoutSubmitView::class)
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/impersonate/{actorId}', ImpersonateView::class)->name('impersonate');
    Route::post('/stop-impersonation', StopImpersonationView::class)->name('stop-impersonation');
});

Route::middleware(['auth', 'owner'])->prefix('profile')->group(function () {
    Route::get('/', ProfileShowView::class)->name('profile.show');
    Route::get('/edit', ProfileEditView::class)->name('profile.edit');
    Route::put('/', ProfileUpdateView::class)->name('profile.update');
});

Route::middleware(['auth', 'owner'])->prefix('guests')->group(function () {
    Route::get('/', UserListView::class)->name('guests.index');
    Route::get('/create', UserCreateView::class)->name('guests.create');
    Route::post('/', UserStoreView::class)->name('guests.store');
    Route::get('/{id}', UserShowView::class)->name('guests.show');
    Route::get('/{id}/edit', UserEditView::class)->name('guests.edit');
    Route::put('/{id}', UserUpdateView::class)->name('guests.update');
    Route::delete('/{id}', UserDeleteView::class)->name('guests.delete');
});
