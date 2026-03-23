<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Shared\Application\Messaging\IntegrationEvent;

final readonly class ReservationCancelledEvent implements IntegrationEvent
{
    public function __construct(
        public string $reservationId,
        public string $stayId,
        public string $checkIn,
        public string $checkOut,
        public string $reason,
        public ?string $freeCancellationUntil,
        public DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
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
