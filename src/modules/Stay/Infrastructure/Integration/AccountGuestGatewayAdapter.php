<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Integration;

use Modules\IAM\Infrastructure\Integration\AccountGuestApi;
use Modules\Stay\Domain\Service\AccountGuestGateway;

final readonly class AccountGuestGatewayAdapter implements AccountGuestGateway
{
    public function __construct(
        private AccountGuestApi $accountGuestApi,
    ) {}

    public function link(string $accountUuid, string $guestUuid): void
    {
        $this->accountGuestApi->link($accountUuid, $guestUuid);
    }

    /** @return list<string> */
    public function guestUuidsForAccount(string $accountUuid): array
    {
        return $this->accountGuestApi->guestUuidsForAccount($accountUuid);
    }
}
