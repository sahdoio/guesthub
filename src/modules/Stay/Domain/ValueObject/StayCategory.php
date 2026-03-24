<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\ValueObject;

enum StayCategory: string
{
    case HOTEL_ROOM = 'hotel_room';
    case HOUSE = 'house';
    case APARTMENT = 'apartment';
}
