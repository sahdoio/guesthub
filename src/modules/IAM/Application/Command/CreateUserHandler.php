<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;

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
