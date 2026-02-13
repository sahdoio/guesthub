<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Command;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;

final readonly class CreateGuestProfileHandler
{
    public function __construct(
        private GuestProfileRepository $repository,
    ) {}

    public function handle(CreateGuestProfile $command): GuestProfileId
    {
        $id = $this->repository->nextIdentity();

        $profile = GuestProfile::create(
            uuid: $id,
            fullName: $command->fullName,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );

        $this->repository->save($profile);

        return $id;
    }
}
