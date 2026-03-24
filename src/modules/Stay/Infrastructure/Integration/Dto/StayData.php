<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Integration\Dto;

final readonly class StayData
{
    /**
     * @param  string[]|null  $amenities
     */
    public function __construct(
        public string $uuid,
        public string $name,
        public string $slug,
        public string $type,
        public string $category,
        public float $pricePerNight,
        public int $capacity,
        public string $status,
        public ?string $description,
        public ?string $address,
        public ?array $amenities,
    ) {}
}
