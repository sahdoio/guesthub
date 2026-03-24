<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\SpecialRequest;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Domain\ValueObject\ReservationStatus;
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
        string $guestId,
        string $accountId,
        string $stayId,
        ReservationPeriod $period,
        int $adults,
        int $children,
        int $babies,
        int $pets,
        ReservationStatus $status,
        array $specialRequests,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $confirmedAt,
        ?DateTimeImmutable $checkedInAt,
        ?DateTimeImmutable $checkedOutAt,
        ?DateTimeImmutable $cancelledAt,
        ?string $cancellationReason,
        ?DateTimeImmutable $freeCancellationUntil,
    ): Reservation {
        $ref = new ReflectionClass(Reservation::class);
        $reservation = $ref->newInstanceWithoutConstructor();

        self::set($ref, $reservation, 'uuid', $uuid);
        self::set($ref, $reservation, 'guestId', $guestId);
        self::set($ref, $reservation, 'accountId', $accountId);
        self::set($ref, $reservation, 'stayId', $stayId);
        self::set($ref, $reservation, 'period', $period);
        self::set($ref, $reservation, 'adults', $adults);
        self::set($ref, $reservation, 'children', $children);
        self::set($ref, $reservation, 'babies', $babies);
        self::set($ref, $reservation, 'pets', $pets);
        self::set($ref, $reservation, 'status', $status);
        self::set($ref, $reservation, 'specialRequests', $specialRequests);
        self::set($ref, $reservation, 'createdAt', $createdAt);
        self::set($ref, $reservation, 'confirmedAt', $confirmedAt);
        self::set($ref, $reservation, 'checkedInAt', $checkedInAt);
        self::set($ref, $reservation, 'checkedOutAt', $checkedOutAt);
        self::set($ref, $reservation, 'cancelledAt', $cancelledAt);
        self::set($ref, $reservation, 'cancellationReason', $cancellationReason);
        self::set($ref, $reservation, 'freeCancellationUntil', $freeCancellationUntil);

        return $reservation;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
