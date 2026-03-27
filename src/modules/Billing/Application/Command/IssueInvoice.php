<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class IssueInvoice extends BaseData
{
    public function __construct(
        public string $invoiceId,
    ) {}
}
