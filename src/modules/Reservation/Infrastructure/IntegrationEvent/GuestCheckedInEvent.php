<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Shared\Application\Messaging\IntegrationEvent;

final readonly class GuestCheckedInEvent implements IntegrationEvent
{
    public function __construct(
        public string $reservationId,
        public string $roomNumber,
        public string $guestEmail,
        public bool $isVip,
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
            'is_vip' => $this->isVip,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
