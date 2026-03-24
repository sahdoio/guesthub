<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

final readonly class ListReservations
{
    public function __construct(
        public ?string $status = null,
        public ?string $guestId = null,
        public ?string $stayId = null,
    ) {}
}
