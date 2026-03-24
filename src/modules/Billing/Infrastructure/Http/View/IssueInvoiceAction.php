<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Billing\Application\Command\IssueInvoice;
use Modules\Billing\Application\Command\IssueInvoiceHandler;

final class IssueInvoiceAction
{
    public function __construct(
        private IssueInvoiceHandler $handler,
    ) {}

    public function __invoke(Request $request, string $uuid): RedirectResponse
    {
        $this->handler->handle(new IssueInvoice(
            invoiceId: $uuid,
        ));

        return redirect()->back()->with('success', 'Invoice issued successfully.');
    }
}
