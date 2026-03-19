<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use Modules\IAM\Domain\HotelId;
use Modules\IAM\Domain\Repository\HotelRepository;

final readonly class UpdateHotelHandler
{
    public function __construct(
        private HotelRepository $repository,
    ) {}

    public function handle(UpdateHotel $command): void
    {
        $hotel = $this->repository->findByUuid(HotelId::fromString($command->hotelId));

        $hotel->updateProfile(
            name: $command->name,
            slug: $command->slug,
            description: $command->description,
            address: $command->address,
            contactEmail: $command->contactEmail,
            contactPhone: $command->contactPhone,
        );

        $this->repository->save($hotel);
    }
}
