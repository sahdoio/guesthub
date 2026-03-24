<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Integration;

use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\StayId;
use Modules\Stay\Infrastructure\Integration\Dto\StayData;

final class StayApi
{
    public function __construct(
        private StayRepository $repository,
    ) {}

    public function findByUuid(string $uuid): ?StayData
    {
        $stay = $this->repository->findByUuid(StayId::fromString($uuid));

        if ($stay === null) {
            return null;
        }

        return new StayData(
            uuid: (string) $stay->uuid,
            name: $stay->name,
            slug: $stay->slug,
            type: $stay->type->value,
            category: $stay->category->value,
            pricePerNight: $stay->pricePerNight,
            capacity: $stay->capacity,
            status: $stay->status,
            description: $stay->description,
            address: $stay->address,
            amenities: $stay->amenities,
        );
    }

    public function isAvailable(string $uuid): bool
    {
        $stay = $this->repository->findByUuid(StayId::fromString($uuid));

        return $stay !== null && $stay->status === 'active';
    }
}
