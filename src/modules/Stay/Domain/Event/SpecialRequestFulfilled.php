<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Event;

use Modules\Shared\Domain\DomainEvent;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\SpecialRequestId;

final class SpecialRequestFulfilled extends DomainEvent
{
    public function __construct(
        public readonly ReservationId $reservationId,
        public readonly SpecialRequestId $requestId,
    ) {
        parent::__construct();
    }
}
