<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Billing\Application\Command\VoidInvoice;
use Modules\Billing\Application\Command\VoidInvoiceHandler;

final class VoidInvoiceAction
{
    public function __construct(
        private VoidInvoiceHandler $handler,
    ) {}

    public function __invoke(Request $request, string $uuid): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->handler->handle(new VoidInvoice(
            invoiceId: $uuid,
            reason: $data['reason'],
        ));

        return redirect()->back()->with('success', 'Invoice voided successfully.');
    }
}
