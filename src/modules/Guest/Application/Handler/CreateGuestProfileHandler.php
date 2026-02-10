<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Handler;

use DateTimeImmutable;
use Modules\Guest\Application\Command\CreateGuestProfile;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;

final class CreateGuestProfileHandler
{
    public function __construct(
        private readonly GuestProfileRepository $repository,
    ) {}

    public function handle(CreateGuestProfile $command): GuestProfileId
    {
        $id = $this->repository->nextIdentity();

        $profile = new GuestProfile(
            uuid: $id,
            fullName: $command->fullName,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
            loyaltyTier: LoyaltyTier::from($command->loyaltyTier),
            preferences: $command->preferences,
            createdAt: new DateTimeImmutable(),
        );

        $this->repository->save($profile);

        return $id;
    }
}
