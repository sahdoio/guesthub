<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Messaging;

use DateTimeImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class IntegrationEvent implements ShouldQueue
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable;
    }

    /** @return array<string, mixed> */
    abstract public function toArray(): array;
}
