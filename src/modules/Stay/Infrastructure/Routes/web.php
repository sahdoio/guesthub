<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Stay\Infrastructure\Http\View\AddSpecialRequestView;
use Modules\Stay\Infrastructure\Http\View\CancelReservationView;
use Modules\Stay\Infrastructure\Http\View\CheckInView;
use Modules\Stay\Infrastructure\Http\View\CheckOutView;
use Modules\Stay\Infrastructure\Http\View\ConfirmReservationView;
use Modules\Stay\Infrastructure\Http\View\ReservationCreateView;
use Modules\Stay\Infrastructure\Http\View\ReservationListView;
use Modules\Stay\Infrastructure\Http\View\ReservationShowView;
use Modules\Stay\Infrastructure\Http\View\ReservationStoreView;
use Modules\Stay\Infrastructure\Http\View\StayCreateView;
use Modules\Stay\Infrastructure\Http\View\StayEditView;
use Modules\Stay\Infrastructure\Http\View\StayImageDeleteView;
use Modules\Stay\Infrastructure\Http\View\StayImageUploadView;
use Modules\Stay\Infrastructure\Http\View\StayListView;
use Modules\Stay\Infrastructure\Http\View\StayShowView;
use Modules\Stay\Infrastructure\Http\View\StayStoreView;
use Modules\Stay\Infrastructure\Http\View\StayUpdateView;

Route::middleware(['auth', 'owner'])->prefix('stays')->group(function () {
    Route::get('/', StayListView::class)->name('stays.index');
    Route::get('/create', StayCreateView::class)->name('stays.create');
    Route::post('/', StayStoreView::class)->name('stays.store');
    Route::get('/{slug}', StayShowView::class)->name('stays.show');
    Route::get('/{slug}/edit', StayEditView::class)->name('stays.edit');
    Route::put('/{slug}', StayUpdateView::class)->name('stays.update');
    Route::post('/{slug}/images', StayImageUploadView::class)->name('stays.images.upload');
    Route::delete('/{slug}/images/{imageUuid}', StayImageDeleteView::class)->name('stays.images.delete');
});

Route::middleware(['auth', 'owner'])->prefix('reservations')->group(function () {
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
