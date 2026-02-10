<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\IntegrationEvent;

use DateTimeImmutable;
use Modules\Shared\Infrastructure\Messaging\IntegrationEvent;

final readonly class ReservationCancelledEvent implements IntegrationEvent
{
    public function __construct(
        public string $reservationId,
        public string $roomType,
        public string $checkIn,
        public string $checkOut,
        public string $reason,
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
            'room_type' => $this->roomType,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'reason' => $this->reason,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
