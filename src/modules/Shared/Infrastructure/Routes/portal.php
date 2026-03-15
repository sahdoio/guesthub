<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalAddSpecialRequestView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalCancelReservationView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalDashboardView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalProfileEditView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalProfileUpdateView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalProfileView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationCreateView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationShowView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationStoreView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationsView;

Route::middleware(['auth', 'portal'])->prefix('portal')->group(function () {
    Route::get('/', PortalDashboardView::class)->name('portal.dashboard');

    Route::get('/reservations', PortalReservationsView::class)->name('portal.reservations.index');
    Route::get('/reservations/create', PortalReservationCreateView::class)->name('portal.reservations.create');
    Route::post('/reservations', PortalReservationStoreView::class)->name('portal.reservations.store');
    Route::get('/reservations/{id}', PortalReservationShowView::class)->name('portal.reservations.show');
    Route::post('/reservations/{id}/cancel', PortalCancelReservationView::class)->name('portal.reservations.cancel');
    Route::post('/reservations/{id}/special-requests', PortalAddSpecialRequestView::class)->name('portal.reservations.special-requests');

    Route::get('/profile', PortalProfileView::class)->name('portal.profile.show');
    Route::get('/profile/edit', PortalProfileEditView::class)->name('portal.profile.edit');
    Route::put('/profile', PortalProfileUpdateView::class)->name('portal.profile.update');
});
