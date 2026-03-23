<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\ValueObject;

enum StayType: string
{
    case ROOM = 'room';
    case ENTIRE_SPACE = 'entire_space';
}
