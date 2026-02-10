<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\ValueObject;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::FULFILLED => 'Fulfilled',
            self::CANCELLED => 'Cancelled',
        };
    }
}
