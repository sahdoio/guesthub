<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class VoidInvoice extends BaseData
{
    public function __construct(
        public string $invoiceId,
        public string $reason,
    ) {}
}
