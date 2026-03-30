<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Service;

interface AccountGuestGateway
{
    public function link(string $accountUuid, string $guestUuid): void;

    /** @return list<string> */
    public function guestUuidsForAccount(string $accountUuid): array;
}
