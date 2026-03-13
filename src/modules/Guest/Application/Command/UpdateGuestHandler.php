<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Command;

use Modules\Guest\Domain\Exception\GuestNotFoundException;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;

final readonly class UpdateGuestHandler
{
    public function __construct(
        private GuestRepository $repository,
    ) {}

    public function handle(UpdateGuest $command): void
    {
        $id = GuestId::fromString($command->guestId);
        $guest = $this->repository->findByUuid($id)
            ?? throw GuestNotFoundException::withId($id);

        if ($command->fullName !== null || $command->email !== null || $command->phone !== null) {
            $guest->updateContactInfo(
                fullName: $command->fullName ?? $guest->fullName,
                email: $command->email ?? $guest->email,
                phone: $command->phone ?? $guest->phone,
            );
        }

        if ($command->loyaltyTier !== null) {
            $guest->changeLoyaltyTier(LoyaltyTier::from($command->loyaltyTier));
        }

        if ($command->preferences !== null) {
            $guest->setPreferences($command->preferences);
        }

        $this->repository->save($guest);
    }
}
