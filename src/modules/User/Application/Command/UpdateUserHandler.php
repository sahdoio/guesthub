<?php

declare(strict_types=1);

namespace Modules\User\Application\Command;

use Modules\User\Domain\Exception\UserNotFoundException;
use Modules\User\Domain\UserId;
use Modules\User\Domain\Repository\UserRepository;
use Modules\User\Domain\ValueObject\LoyaltyTier;

final readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function handle(UpdateUser $command): void
    {
        $id = UserId::fromString($command->userId);
        $user = $this->repository->findByUuid($id)
            ?? throw UserNotFoundException::withUuid($command->userId);

        if ($command->fullName !== null || $command->email !== null || $command->phone !== null) {
            $user->updateContactInfo(
                fullName: $command->fullName ?? $user->fullName,
                email: $command->email ?? $user->email,
                phone: $command->phone ?? $user->phone,
            );
        }

        if ($command->loyaltyTier !== null) {
            $user->changeLoyaltyTier(LoyaltyTier::from($command->loyaltyTier));
        }

        if ($command->preferences !== null) {
            $user->setPreferences($command->preferences);
        }

        $this->repository->save($user);
    }
}
