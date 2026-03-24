<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Domain\Repository\InvoiceRepository;

final class InvoiceShowView
{
    public function __construct(
        private InvoiceRepository $repository,
    ) {}

    public function __invoke(Request $request, string $uuid): Response
    {
        $invoice = $this->repository->findForOwnerView($uuid);

        if (! $invoice) {
            abort(404, 'Invoice not found.');
        }

        return Inertia::render('Billing/Show', [
            'invoice' => $invoice,
        ]);
    }
}
