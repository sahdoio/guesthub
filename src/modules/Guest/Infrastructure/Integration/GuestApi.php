<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Integration;

use Modules\Guest\Application\Command\CreateGuest;
use Modules\Guest\Application\Command\CreateGuestHandler;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Infrastructure\Integration\Dto\GuestData;
use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;

final readonly class GuestApi
{
    public function __construct(
        private CreateGuestHandler $createHandler,
        private GuestRepository $repository,
    ) {}

    public function create(string $name, string $email, string $phone, string $document): int
    {
        $id = $this->createHandler->handle(new CreateGuest(
            fullName: $name,
            email: $email,
            phone: $phone,
            document: $document,
        ));

        return GuestModel::where('uuid', (string) $id)->value('id');
    }

    public function findByUuid(string $uuid): ?GuestData
    {
        $guest = $this->repository->findByUuid(GuestId::fromString($uuid));

        if ($guest === null) {
            return null;
        }

        return new GuestData(
            uuid: (string) $guest->uuid,
            fullName: $guest->fullName,
            email: $guest->email,
            phone: $guest->phone,
            document: $guest->document,
            loyaltyTier: $guest->loyaltyTier->value,
        );
    }
}
