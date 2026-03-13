<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Event;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\Shared\Domain\DomainEvent;

final readonly class AccountCreated implements DomainEvent
{
    public DateTimeImmutable $occurredOn;

    public function __construct(
        public AccountId $accountId,
        public string $name,
    ) {
        $this->occurredOn = new DateTimeImmutable;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
