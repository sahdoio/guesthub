<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\IntegrationEvent;

use Modules\Shared\Infrastructure\Messaging\IntegrationEvent;

final class InvoiceFullyPaidEvent extends IntegrationEvent
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $reservationId,
    ) {
        parent::__construct();
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
