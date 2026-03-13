<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObject;

enum RoomStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';
    case OUT_OF_ORDER = 'out_of_order';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::MAINTENANCE => 'Maintenance',
            self::OUT_OF_ORDER => 'Out of Order',
        };
    }
}
