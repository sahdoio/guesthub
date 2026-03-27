<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Event;

use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\PaymentId;
use Modules\Shared\Domain\DomainEvent;

final class PaymentRecorded extends DomainEvent
{
    public function __construct(
        public readonly InvoiceId $invoiceId,
        public readonly PaymentId $paymentId,
    ) {
        parent::__construct();
    }
}
