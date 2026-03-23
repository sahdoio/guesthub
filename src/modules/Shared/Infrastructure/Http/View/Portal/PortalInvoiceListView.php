<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Infrastructure\Persistence\Eloquent\InvoiceModel;

final class PortalInvoiceListView
{
    public function __invoke(Request $request): Response
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $invoices = InvoiceModel::query()
            ->withoutGlobalScopes()
            ->with(['lineItems', 'payments'])
            ->where('guest_id', $guestUuid)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Portal/Billing/Index', [
            'invoices' => $invoices->toArray(),
        ]);
    }
}
