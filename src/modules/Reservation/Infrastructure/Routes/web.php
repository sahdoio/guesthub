<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservation\Infrastructure\Http\View\AddSpecialRequestView;
use Modules\Reservation\Infrastructure\Http\View\CancelReservationView;
use Modules\Reservation\Infrastructure\Http\View\CheckInView;
use Modules\Reservation\Infrastructure\Http\View\CheckOutView;
use Modules\Reservation\Infrastructure\Http\View\ConfirmReservationView;
use Modules\Reservation\Infrastructure\Http\View\ReservationCreateView;
use Modules\Reservation\Infrastructure\Http\View\ReservationListView;
use Modules\Reservation\Infrastructure\Http\View\ReservationShowView;
use Modules\Reservation\Infrastructure\Http\View\ReservationStoreView;

Route::middleware(['auth', 'admin'])->prefix('reservations')->group(function () {
    Route::get('/', ReservationListView::class)->name('reservations.index');
    Route::get('/create', ReservationCreateView::class)->name('reservations.create');
    Route::post('/', ReservationStoreView::class)->name('reservations.store');
    Route::get('/{id}', ReservationShowView::class)->name('reservations.show');
    Route::post('/{id}/confirm', ConfirmReservationView::class)->name('reservations.confirm');
    Route::post('/{id}/check-in', CheckInView::class)->name('reservations.check-in');
    Route::post('/{id}/check-out', CheckOutView::class)->name('reservations.check-out');
    Route::post('/{id}/cancel', CancelReservationView::class)->name('reservations.cancel');
    Route::post('/{id}/special-requests', AddSpecialRequestView::class)->name('reservations.special-requests');
});
