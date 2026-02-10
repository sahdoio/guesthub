<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Service;

use Modules\Reservation\Domain\Dto\RoomAvailability;
use Modules\Reservation\Domain\Dto\RoomTypeInfo;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;

interface InventoryGateway
{
    public function checkAvailability(string $roomType, ReservationPeriod $period): RoomAvailability;

    public function getRoomTypeInfo(string $roomType): RoomTypeInfo;
}
