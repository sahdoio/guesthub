<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Http\View\DashboardView;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect('/login');
    }

    $user = Auth::user();
    $user->load('roles');
    $roleNames = $user->roles->pluck('name')->toArray();

    if (in_array('admin', $roleNames, true) || in_array('superadmin', $roleNames, true)) {
        return redirect('/dashboard');
    }

    return redirect('/portal');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', DashboardView::class)->name('dashboard');
});

require __DIR__.'/../modules/Shared/Infrastructure/Routes/portal.php';
