<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Billing\Application\Command\RefundInvoice;
use Modules\Billing\Application\Command\RefundInvoiceHandler;

final class RefundInvoiceAction
{
    public function __construct(
        private RefundInvoiceHandler $handler,
    ) {}

    public function __invoke(Request $request, string $uuid): RedirectResponse
    {
        $this->handler->handle(new RefundInvoice(
            invoiceId: $uuid,
        ));

        return redirect()->back()->with('success', 'Invoice refunded successfully.');
    }
}
