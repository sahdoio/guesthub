<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObject;

enum RoomType: string
{
    case SINGLE = 'SINGLE';
    case DOUBLE = 'DOUBLE';
    case SUITE = 'SUITE';
}
