<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use DateTimeImmutable;
use Modules\IAM\Domain\UserId;
use Modules\Shared\Domain\DomainEvent;

final readonly class UserCreated implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public UserId $userId,
        public string $email,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
