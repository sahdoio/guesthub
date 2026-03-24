<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalAddSpecialRequestView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalCancelReservationView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalDashboardView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalInitiatePaymentAction;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalInvoiceListView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalInvoiceShowView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalProfileEditView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalProfileUpdateView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalProfileView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationCheckoutView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationShowView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationStoreView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalReservationsView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalStayListView;
use Modules\Shared\Infrastructure\Http\View\Portal\PortalStayShowView;

Route::middleware(['auth', 'portal'])->prefix('portal')->group(function () {
    Route::get('/', PortalDashboardView::class)->name('portal.dashboard');

    // Stay browsing
    Route::get('/stays', PortalStayListView::class)->name('portal.stays.index');
    Route::get('/stays/{slug}', PortalStayShowView::class)->name('portal.stays.show');

    // Reservations
    Route::get('/reservations', PortalReservationsView::class)->name('portal.reservations.index');
    Route::post('/reservations', PortalReservationStoreView::class)->name('portal.reservations.store');
    Route::get('/reservations/{id}', PortalReservationShowView::class)->name('portal.reservations.show');
    Route::get('/reservations/{id}/checkout', PortalReservationCheckoutView::class)->name('portal.reservations.checkout');
    Route::post('/reservations/{id}/cancel', PortalCancelReservationView::class)->name('portal.reservations.cancel');
    Route::post('/reservations/{id}/special-requests', PortalAddSpecialRequestView::class)->name('portal.reservations.special-requests');

    // Billing
    Route::get('/billing', PortalInvoiceListView::class)->name('portal.billing.index');
    Route::get('/billing/{uuid}', PortalInvoiceShowView::class)->name('portal.billing.show');
    Route::post('/billing/{uuid}/pay', PortalInitiatePaymentAction::class)->name('portal.billing.pay');

    // Profile
    Route::get('/profile', PortalProfileView::class)->name('portal.profile.show');
    Route::get('/profile/edit', PortalProfileEditView::class)->name('portal.profile.edit');
    Route::put('/profile', PortalProfileUpdateView::class)->name('portal.profile.update');
});
