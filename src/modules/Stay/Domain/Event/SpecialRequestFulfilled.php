<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Event;

use DateTimeImmutable;
use Modules\Shared\Domain\DomainEvent;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\SpecialRequestId;

final readonly class SpecialRequestFulfilled implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public ReservationId $reservationId,
        public SpecialRequestId $requestId,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
