<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\IAM\Infrastructure\Http\View\SuperadminHomeView;
use Modules\Shared\Infrastructure\Http\View\DashboardView;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect('/login');
    }

    $user = Auth::user();
    $user->load('types');
    $typeNames = $user->types->pluck('name')->toArray();

    if (in_array('superadmin', $typeNames, true)) {
        return redirect('/superadmin');
    }

    if (in_array('owner', $typeNames, true)) {
        return redirect('/dashboard');
    }

    return redirect('/portal');
});

Route::middleware(['auth', 'type:superadmin'])->group(function () {
    Route::get('/superadmin', SuperadminHomeView::class)->name('superadmin.home');
});

Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/dashboard', DashboardView::class)->name('dashboard');
});

require __DIR__.'/../modules/Shared/Infrastructure/Routes/portal.php';
