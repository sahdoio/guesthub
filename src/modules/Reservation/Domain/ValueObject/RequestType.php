<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\ValueObject;

enum RequestType: string
{
    case EARLY_CHECK_IN = 'early_check_in';
    case LATE_CHECK_OUT = 'late_check_out';
    case EXTRA_BED = 'extra_bed';
    case DIETARY_RESTRICTION = 'dietary_restriction';
    case SPECIAL_OCCASION = 'special_occasion';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EARLY_CHECK_IN => 'Early Check-In',
            self::LATE_CHECK_OUT => 'Late Check-Out',
            self::EXTRA_BED => 'Extra Bed',
            self::DIETARY_RESTRICTION => 'Dietary Restriction',
            self::SPECIAL_OCCASION => 'Special Occasion',
            self::OTHER => 'Other',
        };
    }
}
