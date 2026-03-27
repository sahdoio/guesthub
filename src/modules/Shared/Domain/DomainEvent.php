<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

use DateTimeImmutable;

abstract class DomainEvent
{
    public readonly DateTimeImmutable $occurredOn;

    public function __construct()
    {
        $this->occurredOn = new DateTimeImmutable;
    }
}
