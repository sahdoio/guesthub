<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Domain\ValueObject\TypeName;
use Modules\IAM\Domain\ValueObject\UserId;

final readonly class ProvisionActorAccountHandler
{
    public function __construct(
        private ActorRepository $actorRepository,
        private AccountRepository $accountRepository,
        private TypeRepository $typeRepository,
        private UserRepository $userRepository,
        private EmailUniquenessChecker $emailUniquenessChecker,
    ) {}

    public function handle(ProvisionActorAccount $command): void
    {
        $accountId = $this->accountRepository->nextIdentity();
        $account = Account::create(
            uuid: $accountId,
            name: $command->accountName ?? $command->name."'s Account",
            slug: $command->accountSlug ?? '',
            createdAt: new DateTimeImmutable,
        );
        $this->accountRepository->save($account);

        $userId = UserId::fromString($command->userId);
        $userNumericId = $this->userRepository->resolveNumericId($userId);

        $typeName = TypeName::from($command->actorType);
        $actorType = $this->typeRepository->findByName($typeName);
        $actorId = $this->actorRepository->nextIdentity();

        $actor = Actor::register(
            uuid: $actorId,
            accountId: $accountId,
            typeIds: [$actorType->uuid],
            name: $command->name,
            email: $command->email,
            password: new HashedPassword($command->hashedPassword),
            userId: $userNumericId,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailUniquenessChecker,
        );

        $this->actorRepository->save($actor);
    }
}
