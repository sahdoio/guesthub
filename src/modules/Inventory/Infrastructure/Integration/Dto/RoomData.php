<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Integration\Dto;

final readonly class RoomData
{
    /**
     * @param string[] $amenities
     */
    public function __construct(
        public string $uuid,
        public string $number,
        public string $type,
        public int $floor,
        public int $capacity,
        public float $pricePerNight,
        public string $status,
        public array $amenities,
    ) {}
}
