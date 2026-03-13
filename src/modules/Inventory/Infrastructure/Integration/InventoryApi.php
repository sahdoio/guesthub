<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Integration;

use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Infrastructure\Integration\Dto\RoomData;

readonly class InventoryApi
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    public function findByUuid(string $uuid): ?RoomData
    {
        $room = $this->repository->findByUuid(RoomId::fromString($uuid));

        if ($room === null) {
            return null;
        }

        return new RoomData(
            uuid: (string) $room->uuid,
            number: $room->number,
            type: $room->type->value,
            floor: $room->floor,
            capacity: $room->capacity,
            pricePerNight: $room->pricePerNight,
            status: $room->status->value,
            amenities: $room->amenities,
        );
    }

    public function findByNumber(string $number): ?RoomData
    {
        $room = $this->repository->findByNumber($number);

        if ($room === null) {
            return null;
        }

        return new RoomData(
            uuid: (string) $room->uuid,
            number: $room->number,
            type: $room->type->value,
            floor: $room->floor,
            capacity: $room->capacity,
            pricePerNight: $room->pricePerNight,
            status: $room->status->value,
            amenities: $room->amenities,
        );
    }

    public function countAvailableByType(string $type): int
    {
        return $this->repository->countAvailableByType($type);
    }

    /**
     * @return RoomData[]
     */
    public function listAvailableByType(string $type): array
    {
        $result = $this->repository->list(status: 'available', type: $type, perPage: 100);

        return array_map(
            fn ($room) => new RoomData(
                uuid: (string) $room->uuid,
                number: $room->number,
                type: $room->type->value,
                floor: $room->floor,
                capacity: $room->capacity,
                pricePerNight: $room->pricePerNight,
                status: $room->status->value,
                amenities: $room->amenities,
            ),
            $result->items,
        );
    }

    public function isRoomAvailable(string $roomNumber): bool
    {
        $room = $this->repository->findByNumber($roomNumber);

        return $room !== null && $room->status->value === 'available';
    }
}
