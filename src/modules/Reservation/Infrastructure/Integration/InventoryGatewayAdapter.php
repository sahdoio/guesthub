<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Integration;

use Modules\Reservation\Domain\Dto\RoomAvailability;
use Modules\Reservation\Domain\Dto\RoomTypeInfo;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;

/**
 * Stub implementation of InventoryGateway.
 * TODO: Replace with real integration calling BC2's InventoryApi.
 */
final class InventoryGatewayAdapter implements InventoryGateway
{
    public function checkAvailability(string $roomType, ReservationPeriod $period): RoomAvailability
    {
        // TODO: Call BC2 InventoryApi->checkAvailability() and translate response
        $prices = [
            'SINGLE' => 150.00,
            'DOUBLE' => 250.00,
            'SUITE' => 500.00,
        ];

        return new RoomAvailability(
            roomType: $roomType,
            availableCount: 10,
            pricePerNight: $prices[$roomType] ?? 200.00,
        );
    }

    public function getRoomTypeInfo(string $roomType): RoomTypeInfo
    {
        // TODO: Call BC2 InventoryApi->getRoomTypeInfo() and translate response
        $types = [
            'SINGLE' => new RoomTypeInfo('SINGLE', 'Standard single room', 1, ['wifi', 'tv']),
            'DOUBLE' => new RoomTypeInfo('DOUBLE', 'Comfortable double room', 2, ['wifi', 'tv', 'minibar']),
            'SUITE' => new RoomTypeInfo('SUITE', 'Luxury suite', 4, ['wifi', 'tv', 'minibar', 'jacuzzi', 'balcony']),
        ];

        return $types[$roomType] ?? new RoomTypeInfo($roomType, 'Unknown room type', 2, ['wifi']);
    }
}
