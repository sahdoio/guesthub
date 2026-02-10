<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

use DateTimeImmutable;

final readonly class CreateReservation
{
    public function __construct(
        public string $guestFullName,
        public string $guestEmail,
        public string $guestPhone,
        public string $guestDocument,
        public bool $isVip,
        public DateTimeImmutable $checkIn,
        public DateTimeImmutable $checkOut,
        public string $roomType,
    ) {}
}
