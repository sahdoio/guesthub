<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

final readonly class RefundInvoice
{
    public function __construct(
        public string $invoiceId,
    ) {}
}
