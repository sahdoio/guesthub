<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\IAM\Infrastructure\Integration\Dto\UserData;

final readonly class UserApi
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function findByUuid(string $uuid): ?UserData
    {
        $user = $this->repository->findByUuid(UserId::fromString($uuid));

        if ($user === null) {
            return null;
        }

        return new UserData(
            uuid: (string) $user->uuid,
            fullName: $user->fullName,
            email: $user->email,
            phone: $user->phone,
            document: $user->document,
            loyaltyTier: $user->loyaltyTier?->value,
        );
    }
}
