<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

interface AccountGuestRepository
{
    public function link(string $accountUuid, string $guestUuid): void;

    /** @return list<string> */
    public function guestUuidsForAccount(string $accountUuid): array;
}
