<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Handler;

use Modules\Guest\Application\Command\UpdateGuestProfile;
use Modules\Guest\Domain\Exception\GuestProfileNotFoundException;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;

final class UpdateGuestProfileHandler
{
    public function __construct(
        private readonly GuestProfileRepository $repository,
    ) {}

    public function handle(UpdateGuestProfile $command): void
    {
        $id = GuestProfileId::fromString($command->guestProfileId);
        $profile = $this->repository->findByUuid($id)
            ?? throw GuestProfileNotFoundException::withId($id);

        if ($command->fullName !== null || $command->email !== null || $command->phone !== null) {
            $profile->updateContactInfo(
                fullName: $command->fullName ?? $profile->fullName(),
                email: $command->email ?? $profile->email(),
                phone: $command->phone ?? $profile->phone(),
            );
        }

        if ($command->loyaltyTier !== null) {
            $profile->changeLoyaltyTier(LoyaltyTier::from($command->loyaltyTier));
        }

        if ($command->preferences !== null) {
            $profile->setPreferences($command->preferences);
        }

        $this->repository->save($profile);
    }
}
