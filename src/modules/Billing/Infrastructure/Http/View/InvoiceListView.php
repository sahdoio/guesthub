<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Domain\Repository\InvoiceRepository;

final class InvoiceListView
{
    public function __construct(
        private InvoiceRepository $repository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $result = $this->repository->listForOwnerView(
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 15),
            status: $request->query('status'),
        );

        return Inertia::render('Billing/Index', [
            'invoices' => $result['items'],
            'meta' => $result['meta'],
            'filters' => [
                'status' => $request->query('status'),
            ],
        ]);
    }
}
