<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\Shared\Application\BaseData;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;

final readonly class UpdateStay extends BaseData
{
    public function __construct(
        public string $stayId,
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
