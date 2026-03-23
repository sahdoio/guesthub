<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Integration;

use Modules\IAM\Application\Command\CreateUser;
use Modules\IAM\Application\Command\CreateUserHandler;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Infrastructure\Integration\Dto\UserData;

final readonly class UserApi
{
    public function __construct(
        private CreateUserHandler $createHandler,
        private UserRepository $repository,
    ) {}

    public function create(
        string $name,
        string $email,
        string $phone,
        string $document,
        ?string $loyaltyTier = null,
    ): int {
        $id = $this->createHandler->handle(new CreateUser(
            fullName: $name,
            email: $email,
            phone: $phone,
            document: $document,
            loyaltyTier: $loyaltyTier,
        ));

        return $this->repository->resolveNumericId($id);
    }

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
