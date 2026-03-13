<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Command;

use DateTimeImmutable;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;

final readonly class CreateGuestHandler
{
    public function __construct(
        private GuestRepository $repository,
    ) {}

    public function handle(CreateGuest $command): GuestId
    {
        $id = $this->repository->nextIdentity();

        $guest = Guest::create(
            uuid: $id,
            fullName: $command->fullName,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable,
        );

        $this->repository->save($guest);

        return $id;
    }
}
