<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Exception\ActorAlreadyExistsException;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Service\GuestGateway;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\RoleName;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final readonly class RegisterActorHandler
{
    public function __construct(
        private ActorRepository $repository,
        private AccountRepository $accountRepository,
        private PasswordHasher $hasher,
        private GuestGateway $guestGateway,
        private TenantContext $tenantContext,
    ) {}

    public function handle(RegisterActor $command): ActorId
    {
        $existing = $this->repository->findByEmail($command->email);

        if ($existing !== null) {
            throw ActorAlreadyExistsException::withEmail($command->email);
        }

        // Create account for this guest registration
        $accountId = $this->accountRepository->nextIdentity();
        $account = Account::create(
            uuid: $accountId,
            name: $command->accountName,
            createdAt: new DateTimeImmutable,
        );
        $this->accountRepository->save($account);

        // Set tenant context for cross-BC operations
        $numericAccountId = (int) AccountModel::where('uuid', $accountId->value)->value('id');
        $this->tenantContext->set($numericAccountId);

        // Create guest via ACL
        $guestId = $this->guestGateway->create(
            name: $command->name,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
        );

        // Get the guest role
        $guestRole = $this->repository->findRoleByName(RoleName::GUEST);

        $id = $this->repository->nextIdentity();

        $actor = Actor::register(
            uuid: $id,
            accountId: $accountId,
            roles: [$guestRole],
            name: $command->name,
            email: $command->email,
            password: $this->hasher->hash($command->password),
            subjectType: 'guest',
            subjectId: $guestId,
            createdAt: new DateTimeImmutable,
        );

        $this->repository->save($actor);

        return $id;
    }
}
