<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Messaging;

use BackedEnum;
use DateTimeInterface;
use Modules\Shared\Domain\Identity;
use ReflectionClass;

final class EventSerializer
{
    /**
     * @return array{event_type: string, aggregate_type: string|null, aggregate_id: string|null, payload: array<string, mixed>}
     */
    public function serialize(object $event): array
    {
        $reflection = new ReflectionClass($event);
        $payload = [];
        $aggregateId = null;
        $aggregateType = null;

        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            $value = $property->getValue($event);

            if ($value instanceof DateTimeInterface) {
                continue;
            }

            $serialized = $this->serializeValue($value);
            $payload[$name] = $serialized;

            if ($aggregateId === null && $value instanceof Identity) {
                $aggregateId = (string) $value;
                $aggregateType = $this->resolveAggregateType($event::class);
            }
        }

        return [
            'event_type' => $this->shortName($event::class),
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
        ];
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value instanceof Identity) {
            return (string) $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('c');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $value;
    }

    private function shortName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);

        return end($parts);
    }

    private function resolveAggregateType(string $eventClass): ?string
    {
        if (str_contains($eventClass, 'Reservation\\')) {
            return 'Reservation';
        }
        if (str_contains($eventClass, 'Guest\\')) {
            return 'Guest';
        }
        if (str_contains($eventClass, 'IAM\\')) {
            if (str_contains($eventClass, 'Account')) {
                return 'Account';
            }

            return 'Actor';
        }
        if (str_contains($eventClass, 'Inventory\\')) {
            return 'Room';
        }

        return null;
    }
}
