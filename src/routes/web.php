<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Http\View\DashboardView;

Route::get('/', function () {
    return redirect(Auth::check() ? '/dashboard' : '/login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardView::class)->name('dashboard');
});
