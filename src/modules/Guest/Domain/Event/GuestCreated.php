<?php

declare(strict_types=1);

namespace Modules\Guest\Domain\Event;

use DateTimeImmutable;
use Modules\Guest\Domain\GuestId;
use Modules\Shared\Domain\DomainEvent;

final readonly class GuestCreated implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public GuestId $guestId,
        public string $email,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
