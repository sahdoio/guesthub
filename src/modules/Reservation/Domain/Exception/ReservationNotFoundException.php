<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Exception;

use Modules\Reservation\Domain\ReservationId;

final class ReservationNotFoundException extends \DomainException
{
    public static function withId(ReservationId $id): self
    {
        return new self("Reservation with ID '{$id}' not found.");
    }
}
