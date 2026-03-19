<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use DateTimeImmutable;

final readonly class StoredEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $eventType,
        public string $eventCategory,
        public string $eventClass,
        public ?string $aggregateType,
        public ?string $aggregateId,
        public array $payload,
        public DateTimeImmutable $occurredOn,
    ) {}
}
