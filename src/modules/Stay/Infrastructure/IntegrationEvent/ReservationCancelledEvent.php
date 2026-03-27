<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\IntegrationEvent;

use Modules\Shared\Infrastructure\Messaging\IntegrationEvent;

final class ReservationCancelledEvent extends IntegrationEvent
{
    public function __construct(
        public readonly string $reservationId,
        public readonly string $stayId,
        public readonly string $checkIn,
        public readonly string $checkOut,
        public readonly string $reason,
        public readonly ?string $freeCancellationUntil,
    ) {
        parent::__construct();
    }

    public function toArray(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'stay_id' => $this->stayId,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'reason' => $this->reason,
            'free_cancellation_until' => $this->freeCancellationUntil,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
