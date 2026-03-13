<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

use DateTimeImmutable;

final readonly class CreateReservation
{
    public function __construct(
        public string $guestId,
        public DateTimeImmutable $checkIn,
        public DateTimeImmutable $checkOut,
        public string $roomType,
    ) {}
}
