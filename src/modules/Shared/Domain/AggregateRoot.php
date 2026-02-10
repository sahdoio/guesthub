<?php

declare(strict_types=1);

namespace Modules\Shared\Domain;

abstract class AggregateRoot extends Entity
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return DomainEvent[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
