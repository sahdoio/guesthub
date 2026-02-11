<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Messaging;

use DateTimeImmutable;

interface IntegrationEvent
{
    public function occurredAt(): DateTimeImmutable;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
