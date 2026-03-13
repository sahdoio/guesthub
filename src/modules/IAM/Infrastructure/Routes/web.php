<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Infrastructure\Http\View\LoginSubmitView;
use Modules\IAM\Infrastructure\Http\View\LoginView;
use Modules\IAM\Infrastructure\Http\View\LogoutSubmitView;

Route::middleware('guest')->group(function () {
    Route::get('/login', LoginView::class)->name('login');
    Route::post('/login', LoginSubmitView::class);
});

Route::post('/logout', LogoutSubmitView::class)
    ->middleware('auth')
    ->name('logout');
