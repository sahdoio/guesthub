<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Repository;

interface StayGuestRepository
{
    public function link(string $accountUuid, string $guestUuid): void;

    /** @return list<string> */
    public function guestUuidsForAccount(int $accountId): array;
}
