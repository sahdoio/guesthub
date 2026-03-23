<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

final readonly class CheckOutGuest
{
    public function __construct(
        public string $reservationId,
    ) {}
}
