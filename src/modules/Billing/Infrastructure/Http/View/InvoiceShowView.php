<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Infrastructure\Persistence\Eloquent\InvoiceModel;

final class InvoiceShowView
{
    public function __invoke(Request $request, string $uuid): Response
    {
        $invoice = InvoiceModel::query()
            ->with(['lineItems', 'payments', 'reservation.stay', 'guest'])
            ->where('uuid', $uuid)
            ->first();

        if (! $invoice) {
            abort(404, 'Invoice not found.');
        }

        return Inertia::render('Billing/Show', [
            'invoice' => array_merge($invoice->toArray(), [
                'line_items' => $invoice->lineItems->toArray(),
                'payments' => $invoice->payments->toArray(),
            ]),
        ]);
    }
}
