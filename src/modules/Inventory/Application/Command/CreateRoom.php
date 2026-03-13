<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command;

final readonly class CreateRoom
{
    /**
     * @param  string[]  $amenities
     */
    public function __construct(
        public string $number,
        public string $type,
        public int $floor,
        public int $capacity,
        public float $pricePerNight,
        public array $amenities = [],
    ) {}
}
