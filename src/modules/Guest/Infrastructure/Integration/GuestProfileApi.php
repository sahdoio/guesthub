<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Integration;

use Modules\Guest\Application\Command\CreateGuestProfile;
use Modules\Guest\Application\Command\CreateGuestProfileHandler;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Infrastructure\Integration\Dto\GuestProfileData;

final readonly class GuestProfileApi
{
    public function __construct(
        private CreateGuestProfileHandler $createHandler,
        private GuestProfileRepository $repository,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): string
    {
        $id = $this->createHandler->handle(new CreateGuestProfile(
            fullName: $name,
            email: $email,
            phone: $phone,
            document: $document,
        ));

        return (string) $id;
    }

    public function findByUuid(string $uuid): ?GuestProfileData
    {
        $profile = $this->repository->findByUuid(GuestProfileId::fromString($uuid));

        if ($profile === null) {
            return null;
        }

        return new GuestProfileData(
            uuid: (string) $profile->uuid,
            fullName: $profile->fullName,
            email: $profile->email,
            phone: $profile->phone,
            document: $profile->document,
            loyaltyTier: $profile->loyaltyTier->value,
        );
    }
}
