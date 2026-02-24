<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Query;

final readonly class GetReservation
{
    public function __construct(
        public string $reservationId,
    ) {}
}
