<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;

final readonly class CreateStay
{
    public function __construct(
        public string $accountId,
        public string $name,
        public string $slug,
        public StayType $type,
        public StayCategory $category,
        public float $pricePerNight,
        public int $capacity,
        public ?string $description = null,
        public ?string $address = null,
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
        public ?array $amenities = null,
    ) {}
}
