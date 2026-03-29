<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Messaging;

use DateTimeImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * All integration events should be queued to ensure that they are processed asynchronously
 */
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
