<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\ActorId;
use Modules\Shared\Domain\DomainEvent;

final readonly class ActorRegistered implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public ActorId $actorId,
        public ?AccountId $accountId,
        public string $email,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
