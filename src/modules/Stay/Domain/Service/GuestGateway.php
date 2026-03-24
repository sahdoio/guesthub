<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Service;

use Modules\Stay\Domain\Dto\GuestInfo;

interface GuestGateway
{
    public function findByUuid(string $guestId): ?GuestInfo;
}
