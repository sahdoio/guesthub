<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Shared\Application\TransactionManager;

final class RegisterHotelOwnerHandler extends EventDispatchingHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHasher $hasher,
        private readonly UserEmailUniquenessChecker $emailUniquenessChecker,
        private readonly TransactionManager $transaction,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(RegisterHotelOwner $command): UserId
    {
        return $this->transaction->run(function () use ($command) {
            $userId = $this->userRepository->nextIdentity();

            $user = User::create(
                uuid: $userId,
                fullName: $command->ownerName,
                email: $command->email,
                phone: $command->phone,
                document: $command->document,
                loyaltyTier: null,
                preferences: [],
                createdAt: new DateTimeImmutable,
                hashedPassword: $this->hasher->hash($command->password)->value,
                actorType: 'owner',
                emailUniquenessChecker: $this->emailUniquenessChecker,
                accountName: $command->accountName,
                accountSlug: $command->accountSlug,
            );

            $this->userRepository->save($user);
            $this->dispatchEvents($user);

            return $userId;
        });
    }
}
