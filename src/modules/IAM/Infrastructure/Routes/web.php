<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Infrastructure\Http\View\LoginSubmitView;
use Modules\IAM\Infrastructure\Http\View\LoginView;
use Modules\IAM\Infrastructure\Http\View\LogoutSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterSubmitView;
use Modules\IAM\Infrastructure\Http\View\RegisterView;
use Modules\IAM\Infrastructure\Http\View\SwitchAccountView;

Route::middleware('guest')->group(function () {
    Route::get('/login', LoginView::class)->name('login');
    Route::post('/login', LoginSubmitView::class);
    Route::get('/register', RegisterView::class)->name('register');
    Route::post('/register', RegisterSubmitView::class);
});

Route::post('/logout', LogoutSubmitView::class)
    ->middleware('auth')
    ->name('logout');

Route::post('/switch-account', SwitchAccountView::class)
    ->middleware('auth')
    ->name('switch-account');
