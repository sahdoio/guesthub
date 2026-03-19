<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

final readonly class UpdateHotel
{
    public function __construct(
        public string $hotelId,
        public string $name,
        public string $slug,
        public ?string $description = null,
        public ?string $address = null,
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
    ) {}
}
