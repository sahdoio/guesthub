<?php

declare(strict_types=1);

namespace Modules\Guest\Domain\ValueObject;

enum LoyaltyTier: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';
}
