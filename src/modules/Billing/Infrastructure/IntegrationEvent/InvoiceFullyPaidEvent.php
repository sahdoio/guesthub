<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Shared\Application\Messaging\IntegrationEvent;

final readonly class InvoiceFullyPaidEvent implements IntegrationEvent
{
    public function __construct(
        public string $invoiceId,
        public string $reservationId,
        public DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'reservation_id' => $this->reservationId,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
