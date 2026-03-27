<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Event;

use Modules\Shared\Domain\DomainEvent;
use Modules\Stay\Domain\ReservationId;

final class ReservationCancelled extends DomainEvent
{
    public function __construct(
        public readonly ReservationId $reservationId,
        public readonly string $reason,
    ) {
        parent::__construct();
    }
}
