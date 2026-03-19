<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Eloquent;

use Modules\Shared\Application\EventStore;
use Modules\Shared\Application\StoredEvent;

final class EloquentEventStore implements EventStore
{
    public function append(StoredEvent $event): void
    {
        StoredEventModel::query()->create([
            'event_type' => $event->eventType,
            'event_category' => $event->eventCategory,
            'event_class' => $event->eventClass,
            'aggregate_type' => $event->aggregateType,
            'aggregate_id' => $event->aggregateId,
            'payload' => $event->payload,
            'occurred_on' => $event->occurredOn,
        ]);
    }
}
