<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Messaging;

use DateTimeImmutable;
use Modules\Shared\Application\EventStore;
use Modules\Shared\Application\Messaging\IntegrationEvent;
use Modules\Shared\Application\StoredEvent;
use Modules\Shared\Domain\DomainEvent;

final class EventStoreRecorder
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly EventSerializer $serializer,
    ) {}

    public function record(object $event): void
    {
        if ($event instanceof DomainEvent) {
            $this->recordDomainEvent($event);

            return;
        }

        if ($event instanceof IntegrationEvent) {
            $this->recordIntegrationEvent($event);
        }
    }

    private function recordDomainEvent(DomainEvent $event): void
    {
        $data = $this->serializer->serialize($event);

        $this->eventStore->append(new StoredEvent(
            eventType: $data['event_type'],
            eventCategory: 'domain',
            eventClass: $event::class,
            aggregateType: $data['aggregate_type'],
            aggregateId: $data['aggregate_id'],
            payload: $data['payload'],
            occurredOn: $event->occurredOn(),
        ));
    }

    private function recordIntegrationEvent(IntegrationEvent $event): void
    {
        $shortName = (new \ReflectionClass($event))->getShortName();

        $this->eventStore->append(new StoredEvent(
            eventType: $shortName,
            eventCategory: 'integration',
            eventClass: $event::class,
            aggregateType: null,
            aggregateId: $event->toArray()['reservation_id'] ?? null,
            payload: $event->toArray(),
            occurredOn: $event->occurredAt(),
        ));
    }
}
