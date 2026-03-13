<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command;

final readonly class ChangeRoomStatus
{
    public function __construct(
        public string $roomId,
        public string $status,
    ) {}
}
