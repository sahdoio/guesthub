<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use DateTimeImmutable;
use Modules\Shared\Application\BaseData;

final readonly class CreateReservation extends BaseData
{
    public function __construct(
        public string $guestId,
        public string $accountId,
        public string $stayId,
        public DateTimeImmutable $checkIn,
        public DateTimeImmutable $checkOut,
        public int $adults = 1,
        public int $children = 0,
        public int $babies = 0,
        public int $pets = 0,
    ) {}
}
