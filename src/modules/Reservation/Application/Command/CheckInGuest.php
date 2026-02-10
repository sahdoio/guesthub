<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

final readonly class CheckInGuest
{
    public function __construct(
        public string $reservationId,
        public string $roomNumber,
    ) {}
}
