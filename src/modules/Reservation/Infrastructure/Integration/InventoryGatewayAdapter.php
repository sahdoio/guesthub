<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Integration;

use Modules\Inventory\Infrastructure\Integration\InventoryApi;
use Modules\Reservation\Domain\Dto\AvailableRoom;
use Modules\Reservation\Domain\Dto\RoomAvailability;
use Modules\Reservation\Domain\Dto\RoomTypeInfo;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;

final readonly class InventoryGatewayAdapter implements InventoryGateway
{
    public function __construct(
        private InventoryApi $inventoryApi,
    ) {}

    public function checkAvailability(string $roomType, ReservationPeriod $period): RoomAvailability
    {
        $availableCount = $this->inventoryApi->countAvailableByType($roomType);

        $defaultPrices = [
            'SINGLE' => 150.00,
            'DOUBLE' => 250.00,
            'SUITE' => 500.00,
        ];

        return new RoomAvailability(
            roomType: $roomType,
            availableCount: $availableCount,
            pricePerNight: $defaultPrices[$roomType] ?? 200.00,
        );
    }

    public function getRoomTypeInfo(string $roomType): RoomTypeInfo
    {
        $types = [
            'SINGLE' => new RoomTypeInfo('SINGLE', 'Standard single room', 1, ['wifi', 'tv']),
            'DOUBLE' => new RoomTypeInfo('DOUBLE', 'Comfortable double room', 2, ['wifi', 'tv', 'minibar']),
            'SUITE' => new RoomTypeInfo('SUITE', 'Luxury suite', 4, ['wifi', 'tv', 'minibar', 'jacuzzi', 'balcony']),
        ];

        return $types[$roomType] ?? new RoomTypeInfo($roomType, 'Unknown room type', 2, ['wifi']);
    }

    public function listAvailableRooms(string $roomType): array
    {
        return array_map(
            fn ($room) => new AvailableRoom(
                number: $room->number,
                floor: $room->floor,
                pricePerNight: $room->pricePerNight,
            ),
            $this->inventoryApi->listAvailableByType($roomType),
        );
    }

    public function isRoomAvailable(string $roomNumber): bool
    {
        return $this->inventoryApi->isRoomAvailable($roomNumber);
    }
}
