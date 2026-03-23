<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Shared\Application\Messaging\IntegrationEvent;

final readonly class ReservationCreatedEvent implements IntegrationEvent
{
    public function __construct(
        public string $reservationId,
        public string $guestEmail,
        public string $stayId,
        public string $checkIn,
        public string $checkOut,
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
            'guest_email' => $this->guestEmail,
            'stay_id' => $this->stayId,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'is_vip' => $this->isVip,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
