<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Dto;

final readonly class AvailableRoom
{
    public function __construct(
        public string $number,
        public int $floor,
        public float $pricePerNight,
    ) {}
}
