<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use ReflectionClass;

/**
 * Reconstructs a Reservation aggregate from persisted data
 * bypassing constructor to avoid re-recording domain events.
 */
final class ReservationReflector
{
    /** @param SpecialRequest[] $specialRequests */
    public static function reconstruct(
        ReservationId $uuid,
        string $guestProfileId,
        ReservationPeriod $period,
        string $roomType,
        ReservationStatus $status,
        ?string $assignedRoomNumber,
        array $specialRequests,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $confirmedAt,
        ?DateTimeImmutable $checkedInAt,
        ?DateTimeImmutable $checkedOutAt,
        ?DateTimeImmutable $cancelledAt,
        ?string $cancellationReason,
    ): Reservation {
        $ref = new ReflectionClass(Reservation::class);
        $reservation = $ref->newInstanceWithoutConstructor();

        self::set($ref, $reservation, 'uuid', $uuid);
        self::set($ref, $reservation, 'guestProfileId', $guestProfileId);
        self::set($ref, $reservation, 'period', $period);
        self::set($ref, $reservation, 'roomType', $roomType);
        self::set($ref, $reservation, 'status', $status);
        self::set($ref, $reservation, 'assignedRoomNumber', $assignedRoomNumber);
        self::set($ref, $reservation, 'specialRequests', $specialRequests);
        self::set($ref, $reservation, 'createdAt', $createdAt);
        self::set($ref, $reservation, 'confirmedAt', $confirmedAt);
        self::set($ref, $reservation, 'checkedInAt', $checkedInAt);
        self::set($ref, $reservation, 'checkedOutAt', $checkedOutAt);
        self::set($ref, $reservation, 'cancelledAt', $cancelledAt);
        self::set($ref, $reservation, 'cancellationReason', $cancellationReason);

        return $reservation;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
