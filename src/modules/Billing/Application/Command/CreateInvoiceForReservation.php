<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Command;

use Modules\Shared\Application\BaseData;

final readonly class CreateInvoiceForReservation extends BaseData
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
