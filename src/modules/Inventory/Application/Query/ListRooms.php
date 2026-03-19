<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Query;

final readonly class ListRooms
{
    public function __construct(
        public ?string $status = null,
        public ?string $type = null,
        public ?int $floor = null,
        public ?int $hotelId = null,
    ) {}
}
