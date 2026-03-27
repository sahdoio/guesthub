<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Shared\Application\TransactionManager;

final class CreateUserHandler extends EventDispatchingHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly PasswordHasher $hasher,
        private readonly UserEmailUniquenessChecker $emailUniquenessChecker,
        private readonly TransactionManager $transaction,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CreateUser $command): UserId
    {
        return $this->transaction->run(function () use ($command) {
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
                hashedPassword: $this->hasher->hash($command->password)->value,
                actorType: $command->actorType,
                emailUniquenessChecker: $this->emailUniquenessChecker,
                accountName: $command->accountName ?? $command->fullName."'s Account",
                accountSlug: $command->accountSlug ?? Str::slug($command->fullName).'-'.Str::random(6),
            );

            $this->repository->save($user);
            $this->dispatchEvents($user);

            return $id;
        });
    }
}
