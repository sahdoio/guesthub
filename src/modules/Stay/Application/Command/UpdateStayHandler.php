<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\Stay\Domain\StayId;
use Modules\Stay\Domain\Repository\StayRepository;

final readonly class UpdateStayHandler
{
    public function __construct(
        private StayRepository $repository,
    ) {}

    public function handle(UpdateStay $command): void
    {
        $stay = $this->repository->findByUuid(StayId::fromString($command->stayId));

        $stay->updateProfile(
            name: $command->name,
            slug: $command->slug,
            type: $command->type,
            category: $command->category,
            pricePerNight: $command->pricePerNight,
            capacity: $command->capacity,
            description: $command->description,
            address: $command->address,
            contactEmail: $command->contactEmail,
            contactPhone: $command->contactPhone,
            amenities: $command->amenities,
        );

        $this->repository->save($stay);
    }
}
