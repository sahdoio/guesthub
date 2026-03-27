<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Event;

use Modules\Billing\Domain\InvoiceId;
use Modules\Shared\Domain\DomainEvent;

final class InvoiceRefunded extends DomainEvent
{
    public function __construct(
        public readonly InvoiceId $invoiceId,
    ) {
        parent::__construct();
    }
}
