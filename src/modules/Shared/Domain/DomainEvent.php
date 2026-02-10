<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

use DateTimeImmutable;

interface DomainEvent
{
    public function occurredOn(): DateTimeImmutable;
}
