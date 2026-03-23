<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\UserGateway;
use Modules\IAM\Domain\ValueObject\TypeName;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

final class RegisterActorHandler extends EventDispatchingHandler
{
    public function __construct(
        private ActorRepository $repository,
        private AccountRepository $accountRepository,
        private TypeRepository $typeRepository,
        private PasswordHasher $hasher,
        private EmailUniquenessChecker $emailUniquenessChecker,
        private UserGateway $userGateway,
        EventDispatcher $dispatcher
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(RegisterActor $command): ActorId
    {
        // 1. Create Account (personal tenant for the guest)
        $accountId = $this->accountRepository->nextIdentity();
        $account = Account::create(
            uuid: $accountId,
            name: $command->name . "'s Account",
            slug: Str::slug($command->name) . '-' . Str::random(6),
            createdAt: new DateTimeImmutable,
        );
        $this->accountRepository->save($account);

        // 2. Create User profile (global, no tenant scoping)
        $userId = $this->userGateway->create(
            name: $command->name,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
            loyaltyTier: 'bronze',
        );

        // 3. Create Actor with guest type linkage and account
        $actorType = $this->typeRepository->findByName(TypeName::GUEST);
        $id = $this->repository->nextIdentity();

        $actor = Actor::register(
            uuid: $id,
            accountId: $accountId,
            typeIds: [$actorType->uuid],
            name: $command->name,
            email: $command->email,
            password: $this->hasher->hash($command->password),
            userId: $userId,
            createdAt: new DateTimeImmutable,
            emailUniquenessChecker: $this->emailUniquenessChecker,
        );

        $this->repository->save($actor);

        $this->dispatchEvents($account);
        $this->dispatchEvents($actor);

        return $id;
    }
}
