<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Presentation\Http\Presenter\InvoicePresenter;

final class PortalInvoiceListView
{
    public function __construct(
        private InvoiceRepository $repository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $invoices = $this->repository->findAllByGuestIdGlobal($guestUuid);

        return Inertia::render('Portal/Billing/Index', [
            'invoices' => array_map(InvoicePresenter::toArray(...), $invoices),
        ]);
    }
}
