<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

use DateTimeImmutable;
use Modules\User\Domain\User;
use Modules\User\Domain\UserId;
use Modules\User\Domain\Repository\UserRepository;
use Modules\User\Domain\ValueObject\LoyaltyTier;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function handle(CreateUser $command): UserId
    {
        $id = $this->repository->nextIdentity();

        $loyaltyTier = $command->loyaltyTier !== null
            ? LoyaltyTier::from($command->loyaltyTier)
            : null;

        $user = User::create(
            uuid: $id,
            fullName: $command->fullName,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
            loyaltyTier: $loyaltyTier,
            preferences: [],
            createdAt: new DateTimeImmutable,
        );

        $this->repository->save($user);

        return $id;
    }
}
