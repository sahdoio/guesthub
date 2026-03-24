<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

final readonly class ConfirmReservation
{
    public function __construct(
        public string $reservationId,
    ) {}
}
