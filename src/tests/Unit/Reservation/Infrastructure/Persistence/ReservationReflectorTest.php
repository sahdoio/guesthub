<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use Modules\Reservation\Infrastructure\Persistence\ReservationReflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReservationReflector::class)]
final class ReservationReflectorTest extends TestCase
{
    #[Test]
    public function it_reconstructs_a_pending_reservation(): void
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
            hotelId: 'hotel-uuid-123',
            period: $period,
            roomType: 'DOUBLE',
            status: ReservationStatus::PENDING,
            assignedRoomNumber: null,
            specialRequests: [],
            createdAt: $createdAt,
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
        );

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertTrue($uuid->equals($reservation->uuid));
        $this->assertSame('guest-uuid-123', $reservation->guestId);
        $this->assertTrue($period->equals($reservation->period));
        $this->assertSame('DOUBLE', $reservation->roomType);
        $this->assertSame(ReservationStatus::PENDING, $reservation->status);
        $this->assertNull($reservation->assignedRoomNumber);
        $this->assertEmpty($reservation->specialRequests);
        $this->assertSame($createdAt, $reservation->createdAt);
    }

    #[Test]
    public function it_reconstructs_a_checked_in_reservation(): void
    {
        $confirmedAt = new DateTimeImmutable('2026-01-16');
        $checkedInAt = new DateTimeImmutable('2026-01-18');

        $reservation = ReservationReflector::reconstruct(
            uuid: ReservationId::generate(),
            guestId: 'guest-uuid-456',
            accountId: 'account-uuid-456',
            hotelId: 'hotel-uuid-456',
            period: new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+5 days'),
            ),
            roomType: 'SUITE',
            status: ReservationStatus::CHECKED_IN,
            assignedRoomNumber: '501',
            specialRequests: [],
            createdAt: new DateTimeImmutable('2026-01-15'),
            confirmedAt: $confirmedAt,
            checkedInAt: $checkedInAt,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
        );

        $this->assertSame(ReservationStatus::CHECKED_IN, $reservation->status);
        $this->assertSame('501', $reservation->assignedRoomNumber);
        $this->assertSame($confirmedAt, $reservation->confirmedAt);
        $this->assertSame($checkedInAt, $reservation->checkedInAt);
    }

    #[Test]
    public function it_reconstructs_a_cancelled_reservation(): void
    {
        $cancelledAt = new DateTimeImmutable('2026-01-17');

        $reservation = ReservationReflector::reconstruct(
            uuid: ReservationId::generate(),
            guestId: 'guest-uuid-789',
            accountId: 'account-uuid-789',
            hotelId: 'hotel-uuid-789',
            period: new ReservationPeriod(
                new DateTimeImmutable('+5 days'),
                new DateTimeImmutable('+8 days'),
            ),
            roomType: 'SINGLE',
            status: ReservationStatus::CANCELLED,
            assignedRoomNumber: null,
            specialRequests: [],
            createdAt: new DateTimeImmutable('2026-01-15'),
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: $cancelledAt,
            cancellationReason: 'Trip cancelled',
        );

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->status);
        $this->assertSame('Trip cancelled', $reservation->cancellationReason);
        $this->assertSame($cancelledAt, $reservation->cancelledAt);
    }

    #[Test]
    public function it_does_not_record_domain_events(): void
    {
        $reservation = ReservationReflector::reconstruct(
            uuid: ReservationId::generate(),
            guestId: 'guest-uuid',
            accountId: 'account-uuid',
            hotelId: 'hotel-uuid',
            period: new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+3 days'),
            ),
            roomType: 'SINGLE',
            status: ReservationStatus::PENDING,
            assignedRoomNumber: null,
            specialRequests: [],
            createdAt: new DateTimeImmutable,
            confirmedAt: null,
            checkedInAt: null,
            checkedOutAt: null,
            cancelledAt: null,
            cancellationReason: null,
        );

        $this->assertEmpty($reservation->pullDomainEvents());
    }
}
