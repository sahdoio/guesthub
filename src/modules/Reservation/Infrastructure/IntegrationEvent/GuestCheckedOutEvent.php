<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Shared\Application\Messaging\IntegrationEvent;

final readonly class GuestCheckedOutEvent implements IntegrationEvent
{
    public function __construct(
        public string $reservationId,
        public string $roomNumber,
        public string $guestEmail,
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
            'room_number' => $this->roomNumber,
            'guest_email' => $this->guestEmail,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
