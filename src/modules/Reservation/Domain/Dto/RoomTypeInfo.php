<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Dto;

final readonly class RoomTypeInfo
{
    /**
     * @param string[] $amenities
     */
    public function __construct(
        public string $type,
        public string $description,
        public int $capacity,
        public array $amenities,
    ) {}
}
