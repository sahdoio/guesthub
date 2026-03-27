<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Presentation\Http\Presenter\InvoicePresenter;

final class PortalInvoiceShowView
{
    public function __construct(
        private InvoiceRepository $repository,
    ) {}

    public function __invoke(Request $request, string $uuid): Response
    {
        $invoice = $this->repository->findByUuidGlobal(InvoiceId::fromString($uuid));

        if (! $invoice) {
            abort(404, 'Invoice not found.');
        }

        $guestUuid = $request->attributes->get('guest_uuid');
        if ($guestUuid && $invoice->guestId !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        return Inertia::render('Portal/Billing/Show', [
            'invoice' => InvoicePresenter::toArray($invoice),
            'stripePublishableKey' => config('billing.stripe.publishable_key'),
        ]);
    }
}
