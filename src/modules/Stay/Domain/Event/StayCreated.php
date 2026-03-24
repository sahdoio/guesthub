<?php

declare(strict_types=1);

namespace Modules\Stay\Domain\Event;

use DateTimeImmutable;
use Modules\Shared\Domain\DomainEvent;
use Modules\Stay\Domain\StayId;

final readonly class StayCreated implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public StayId $stayId,
        public string $name,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
