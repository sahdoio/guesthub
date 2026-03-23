<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Event;

use DateTimeImmutable;
use Modules\Billing\Domain\InvoiceId;
use Modules\Shared\Domain\DomainEvent;

final readonly class InvoiceFullyPaid implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public InvoiceId $invoiceId,
        public string $reservationId,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
