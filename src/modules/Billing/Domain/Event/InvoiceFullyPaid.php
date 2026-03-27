<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Event;

use Modules\Billing\Domain\InvoiceId;
use Modules\Shared\Domain\DomainEvent;

final class InvoiceFullyPaid extends DomainEvent
{
    public function __construct(
        public readonly InvoiceId $invoiceId,
        public readonly string $reservationId,
    ) {
        parent::__construct();
    }
}
