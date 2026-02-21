<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Query;

final readonly class ListReservations
{
    public function __construct(
        public ?string $status = null,
        public ?string $roomType = null,
    ) {}
}
