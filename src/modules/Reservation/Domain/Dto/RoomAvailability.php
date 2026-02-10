<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Dto;

final readonly class RoomAvailability
{
    public function __construct(
        public string $roomType,
        public int $availableCount,
        public float $pricePerNight,
    ) {}
}
