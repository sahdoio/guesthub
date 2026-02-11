<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Service;

use Modules\Reservation\Domain\Dto\GuestInfo;

interface GuestGateway
{
    public function findByUuid(string $guestProfileId): ?GuestInfo;
}
