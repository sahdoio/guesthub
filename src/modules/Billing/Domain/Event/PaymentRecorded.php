<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Event;

use DateTimeImmutable;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\PaymentId;
use Modules\Shared\Domain\DomainEvent;

final readonly class PaymentRecorded implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public InvoiceId $invoiceId,
        public PaymentId $paymentId,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
