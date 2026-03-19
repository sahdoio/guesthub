<?php

declare(strict_types=1);

namespace Modules\User\Domain\Event;

use DateTimeImmutable;
use Modules\Shared\Domain\DomainEvent;
use Modules\User\Domain\UserId;

final readonly class UserContactInfoUpdated implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public UserId $userId,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
