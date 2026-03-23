<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Infrastructure\Persistence\Eloquent\InvoiceModel;

final class InvoiceListView
{
    public function __invoke(Request $request): Response
    {
        $query = InvoiceModel::query()
            ->with(['lineItems', 'payments', 'reservation.stay', 'guest']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->orderByDesc('created_at')
            ->paginate(
                perPage: (int) $request->query('per_page', 15),
                page: (int) $request->query('page', 1),
            );

        return Inertia::render('Billing/Index', [
            'invoices' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'status' => $request->query('status'),
            ],
        ]);
    }
}
