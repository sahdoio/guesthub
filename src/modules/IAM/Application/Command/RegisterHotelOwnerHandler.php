<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
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

final class RegisterHotelOwnerHandler extends EventDispatchingHandler
{
    public function __construct(
        private ActorRepository $repository,
        private AccountRepository $accountRepository,
        private TypeRepository $typeRepository,
        private UserGateway $userGateway,
        private PasswordHasher $hasher,
        private EmailUniquenessChecker $emailUniquenessChecker,
        EventDispatcher $dispatcher
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(RegisterHotelOwner $command): ActorId
    {
        // 1. Create Account (tenant)
        $accountId = $this->accountRepository->nextIdentity();
        $account = Account::create(
            uuid: $accountId,
            name: $command->accountName,
            slug: $command->accountSlug,
            createdAt: new DateTimeImmutable,
        );
        $this->accountRepository->save($account);

        // 2. Create User profile (no loyalty tier for owners)
        $userId = $this->userGateway->create(
            name: $command->ownerName,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
        );

        // 3. Create Actor with owner type
        $ownerType = $this->typeRepository->findByName(TypeName::OWNER);
        $id = $this->repository->nextIdentity();

        $actor = Actor::register(
            uuid: $id,
            accountId: $accountId,
            typeIds: [$ownerType->uuid],
            name: $command->ownerName,
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
