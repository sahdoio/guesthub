<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Exception;

use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;

final class InvalidReservationStateException extends \DomainException
{
    public static function forTransition(ReservationStatus $from, ReservationStatus $to): self
    {
        return new self("Cannot transition reservation from '{$from->value}' to '{$to->value}'.");
    }

    public static function forRequestTransition(RequestStatus $from, RequestStatus $to): self
    {
        return new self("Cannot transition special request from '{$from->value}' to '{$to->value}'.");
    }
}
