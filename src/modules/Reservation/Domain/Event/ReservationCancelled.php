<?php

declare(strict_types=1);

namespace Modules\Reservation\Domain\Event;

use DateTimeImmutable;
use Modules\Reservation\Domain\ReservationId;
use Modules\Shared\Domain\DomainEvent;

final readonly class ReservationCancelled implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public ReservationId $reservationId,
        public string $reason,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
