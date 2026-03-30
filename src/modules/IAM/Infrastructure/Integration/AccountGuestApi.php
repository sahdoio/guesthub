<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\IAM\Domain\Repository\AccountGuestRepository;

final readonly class AccountGuestApi
{
    public function __construct(
        private AccountGuestRepository $repository,
    ) {}

    public function link(string $accountUuid, string $guestUuid): void
    {
        $this->repository->link($accountUuid, $guestUuid);
    }

    /** @return list<string> */
    public function guestUuidsForAccount(string $accountUuid): array
    {
        return $this->repository->guestUuidsForAccount($accountUuid);
    }
}
