<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Exception;

use Modules\Stay\Domain\ReservationId;

final class ReservationNotFoundException extends \DomainException
{
    public static function withId(ReservationId $id): self
    {
        return new self("Reservation with ID '{$id}' not found.");
    }
}
