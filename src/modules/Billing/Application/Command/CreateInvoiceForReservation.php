<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

final readonly class CreateInvoiceForReservation
{
    public function __construct(
        public string $reservationId,
        public string $accountId,
        public string $guestId,
        public string $stayName,
        public float $pricePerNight,
        public int $nights,
        public string $checkIn,
        public string $checkOut,
        public float $taxRate,
    ) {}
}
