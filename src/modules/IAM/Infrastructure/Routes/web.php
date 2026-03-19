<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Infrastructure\Http\View\HotelCreateView;
use Modules\IAM\Infrastructure\Http\View\HotelEditView;
use Modules\IAM\Infrastructure\Http\View\HotelListView;
use Modules\IAM\Infrastructure\Http\View\HotelShowView;
use Modules\IAM\Infrastructure\Http\View\HotelStoreView;
use Modules\IAM\Infrastructure\Http\View\HotelUpdateView;
use Modules\IAM\Infrastructure\Http\View\ImpersonateView;
use Modules\IAM\Infrastructure\Http\View\LoginSubmitView;
use Modules\IAM\Infrastructure\Http\View\LoginView;
use Modules\IAM\Infrastructure\Http\View\LogoutSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterHotelSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterHotelView;
use Modules\IAM\Infrastructure\Http\View\RegisterSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterView;
use Modules\IAM\Infrastructure\Http\View\StopImpersonationView;

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

Route::middleware(['auth', 'owner'])->prefix('hotels')->group(function () {
    Route::get('/', HotelListView::class)->name('hotels.index');
    Route::get('/create', HotelCreateView::class)->name('hotels.create');
    Route::post('/', HotelStoreView::class)->name('hotels.store');
    Route::get('/{slug}', HotelShowView::class)->name('hotels.show');
    Route::get('/{slug}/edit', HotelEditView::class)->name('hotels.edit');
    Route::put('/{slug}', HotelUpdateView::class)->name('hotels.update');
});
