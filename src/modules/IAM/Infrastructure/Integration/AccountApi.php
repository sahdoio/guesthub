<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\ValueObject\AccountId;

final readonly class AccountApi
{
    public function __construct(
        private AccountRepository $repository,
    ) {}

    public function resolveNumericId(string $accountUuid): ?int
    {
        return $this->repository->resolveNumericId(
            AccountId::fromString($accountUuid),
        );
    }
}
