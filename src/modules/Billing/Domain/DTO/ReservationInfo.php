<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\DTO;

final readonly class ReservationInfo
{
    public function __construct(
        public string $reservationId,
        public string $guestId,
        public string $stayId,
        public string $stayName,
        public string $accountId,
        public string $checkIn,
        public string $checkOut,
        public int $nights,
        public float $pricePerNight,
    ) {}
}
