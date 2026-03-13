<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command;

final readonly class UpdateRoom
{
    /**
     * @param string[]|null $amenities
     */
    public function __construct(
        public string $roomId,
        public ?float $pricePerNight = null,
        public ?array $amenities = null,
    ) {}
}
