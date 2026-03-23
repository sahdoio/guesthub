<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Infrastructure\Http\View\InvoiceListView;
use Modules\Billing\Infrastructure\Http\View\InvoiceShowView;
use Modules\Billing\Infrastructure\Http\View\IssueInvoiceAction;
use Modules\Billing\Infrastructure\Http\View\RefundInvoiceAction;
use Modules\Billing\Infrastructure\Http\View\VoidInvoiceAction;

Route::middleware(['auth', 'owner'])->prefix('billing')->group(function () {
    Route::get('/', InvoiceListView::class)->name('billing.index');
    Route::get('/{uuid}', InvoiceShowView::class)->name('billing.show');
    Route::post('/{uuid}/issue', IssueInvoiceAction::class)->name('billing.issue');
    Route::post('/{uuid}/void', VoidInvoiceAction::class)->name('billing.void');
    Route::post('/{uuid}/refund', RefundInvoiceAction::class)->name('billing.refund');
});
