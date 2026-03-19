<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Integration;

use Modules\User\Application\Command\CreateUser;
use Modules\User\Application\Command\CreateUserHandler;
use Modules\User\Domain\UserId;
use Modules\User\Domain\Repository\UserRepository;
use Modules\User\Infrastructure\Integration\Dto\UserData;

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
