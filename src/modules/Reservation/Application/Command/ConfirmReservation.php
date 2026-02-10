<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Command;

final readonly class ConfirmReservation
{
    public function __construct(
        public string $reservationId,
    ) {}
}
