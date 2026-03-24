<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Service;

use Modules\Billing\Domain\DTO\ReservationInfo;

interface ReservationGateway
{
    public function findReservation(string $reservationId): ?ReservationInfo;
}
