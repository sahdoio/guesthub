<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\ValueObject\RequestStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\SpecialRequestId;
use ReflectionClass;

/**
 * Reconstructs a SpecialRequest entity from persisted data
 * bypassing constructor to restore status and fulfilledAt.
 */
final class SpecialRequestReflector
{
    public static function reconstruct(
        SpecialRequestId $id,
        RequestType $type,
        string $description,
        RequestStatus $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $fulfilledAt,
    ): SpecialRequest {
        $ref = new ReflectionClass(SpecialRequest::class);
        $entity = $ref->newInstanceWithoutConstructor();

        self::set($ref, $entity, 'id', $id);
        self::set($ref, $entity, 'type', $type);
        self::set($ref, $entity, 'description', $description);
        self::set($ref, $entity, 'status', $status);
        self::set($ref, $entity, 'createdAt', $createdAt);
        self::set($ref, $entity, 'fulfilledAt', $fulfilledAt);

        return $entity;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
