<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Infrastructure\Persistence\Eloquent\InvoiceModel;

final class PortalInvoiceShowView
{
    public function __invoke(Request $request, string $uuid): Response
    {
        $invoice = InvoiceModel::query()
            ->withoutGlobalScopes()
            ->with(['lineItems', 'payments'])
            ->where('uuid', $uuid)
            ->first();

        if (! $invoice) {
            abort(404, 'Invoice not found.');
        }

        $guestUuid = $request->attributes->get('guest_uuid');
        if ($guestUuid && $invoice->guest_id !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        return Inertia::render('Portal/Billing/Show', [
            'invoice' => $invoice->toArray(),
            'stripePublishableKey' => config('billing.stripe.publishable_key'),
        ]);
    }
}
