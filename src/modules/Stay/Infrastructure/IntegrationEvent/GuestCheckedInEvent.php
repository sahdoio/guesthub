<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\IntegrationEvent;

use Modules\Shared\Infrastructure\Messaging\IntegrationEvent;

final class GuestCheckedInEvent extends IntegrationEvent
{
    public function __construct(
        public readonly string $reservationId,
        public readonly string $guestEmail,
        public readonly bool $isVip,
    ) {
        parent::__construct();
    }

    public function toArray(): array
    {
        return [
            'reservation_id' => $this->reservationId,
            'guest_email' => $this->guestEmail,
            'is_vip' => $this->isVip,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
