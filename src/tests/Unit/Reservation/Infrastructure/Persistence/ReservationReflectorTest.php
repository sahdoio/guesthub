<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Domain\ValueObject\ReservationStatus;
use Modules\Stay\Infrastructure\Persistence\ReservationReflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReservationReflector::class)]
final class ReservationReflectorTest extends TestCase
{
    #[Test]
    public function itReconstructsAPendingReservation(): void
    {
        $uuid = ReservationId::generate();
        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+4 days'),
        );
        $createdAt = new DateTimeImmutable('2026-01-15 10:00:00');

        $reservation = ReservationReflector::reconstruct(
            uuid: $uuid,
            guestId: 'guest-uuid-123',
            accountId: 'account-uuid-123',
            stayId: 'stay-uuid-123',
            period: $period,
            adults: 2,
            children: 1,
            babies: 0,
            pets: 0,
            status: ReservationStatus::PENDING,
            specialRequests: [],
            createdAt: $createdAt,
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
            freeCancellationUntil: $period->checkIn->modify('-48 hours'),
        );

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertTrue($uuid->equals($reservation->uuid));
        $this->assertSame('guest-uuid-123', $reservation->guestId);
        $this->assertTrue($period->equals($reservation->period));
        $this->assertSame(2, $reservation->adults);
        $this->assertSame(1, $reservation->children);
        $this->assertSame(0, $reservation->babies);
        $this->assertSame(0, $reservation->pets);
        $this->assertSame(ReservationStatus::PENDING, $reservation->status);
        $this->assertEmpty($reservation->specialRequests);
        $this->assertSame($createdAt, $reservation->createdAt);
    }

    #[Test]
    public function itReconstructsACheckedInReservation(): void
    {
        $confirmedAt = new DateTimeImmutable('2026-01-16');
        $checkedInAt = new DateTimeImmutable('2026-01-18');

        $reservation = ReservationReflector::reconstruct(
            uuid: ReservationId::generate(),
            guestId: 'guest-uuid-456',
            accountId: 'account-uuid-456',
            stayId: 'stay-uuid-456',
            period: new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+5 days'),
            ),
            adults: 1,
            children: 0,
            babies: 0,
            pets: 0,
            status: ReservationStatus::CHECKED_IN,
            specialRequests: [],
            createdAt: new DateTimeImmutable('2026-01-15'),
            confirmedAt: $confirmedAt,
            checkedInAt: $checkedInAt,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
            freeCancellationUntil: null,
        );

        $this->assertSame(ReservationStatus::CHECKED_IN, $reservation->status);
        $this->assertSame($confirmedAt, $reservation->confirmedAt);
        $this->assertSame($checkedInAt, $reservation->checkedInAt);
    }

    #[Test]
    public function itReconstructsACancelledReservation(): void
    {
        $cancelledAt = new DateTimeImmutable('2026-01-17');

        $reservation = ReservationReflector::reconstruct(
            uuid: ReservationId::generate(),
            guestId: 'guest-uuid-789',
            accountId: 'account-uuid-789',
            stayId: 'stay-uuid-789',
            period: new ReservationPeriod(
                new DateTimeImmutable('+5 days'),
                new DateTimeImmutable('+8 days'),
            ),
            adults: 1,
            children: 0,
            babies: 0,
            pets: 0,
            status: ReservationStatus::CANCELLED,
            specialRequests: [],
            createdAt: new DateTimeImmutable('2026-01-15'),
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: $cancelledAt,
            cancellationReason: 'Trip cancelled',
            freeCancellationUntil: null,
        );

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->status);
        $this->assertSame('Trip cancelled', $reservation->cancellationReason);
        $this->assertSame($cancelledAt, $reservation->cancelledAt);
    }

    #[Test]
    public function itDoesNotRecordDomainEvents(): void
    {
        $reservation = ReservationReflector::reconstruct(
            uuid: ReservationId::generate(),
            guestId: 'guest-uuid',
            accountId: 'account-uuid',
            stayId: 'stay-uuid',
            period: new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+3 days'),
            ),
            adults: 1,
            children: 0,
            babies: 0,
            pets: 0,
            status: ReservationStatus::PENDING,
            specialRequests: [],
            createdAt: new DateTimeImmutable,
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
            freeCancellationUntil: null,
        );

        $this->assertEmpty($reservation->pullDomainEvents());
    }
}
