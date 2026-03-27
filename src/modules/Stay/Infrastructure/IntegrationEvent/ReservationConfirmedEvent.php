<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\IntegrationEvent;

use Modules\Shared\Infrastructure\Messaging\IntegrationEvent;

final class ReservationConfirmedEvent extends IntegrationEvent
{
    public function __construct(
        public readonly string $reservationId,
        public readonly string $guestEmail,
        public readonly string $stayId,
        public readonly string $checkIn,
        public readonly string $checkOut,
        public readonly bool $isVip,
    ) {
        parent::__construct();
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
