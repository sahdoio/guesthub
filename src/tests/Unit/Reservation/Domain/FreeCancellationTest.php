<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain;

use DateTimeImmutable;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(Reservation::class)]
final class FreeCancellationTest extends TestCase
{
    #[Test]
    public function it_sets_free_cancellation_until_48h_before_checkin(): void
    {
        $checkIn = new DateTimeImmutable('+10 days');
        $checkOut = new DateTimeImmutable('+13 days');

        $reservation = Reservation::create(
            ReservationId::generate(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            new ReservationPeriod($checkIn, $checkOut),
        );

        $expected = $checkIn->modify('-48 hours');
        $this->assertEquals($expected, $reservation->freeCancellationUntil);
    }

    #[Test]
    public function it_is_within_free_cancellation_window_when_checkin_is_far(): void
    {
        $checkIn = new DateTimeImmutable('+10 days');
        $checkOut = new DateTimeImmutable('+13 days');

        $reservation = Reservation::create(
            ReservationId::generate(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            new ReservationPeriod($checkIn, $checkOut),
        );

        $this->assertTrue($reservation->isWithinFreeCancellationWindow());
    }

    #[Test]
    public function it_is_outside_free_cancellation_window_when_checkin_is_soon(): void
    {
        // Check-in is in 1 hour — 48h window has passed
        $checkIn = new DateTimeImmutable('+1 hour');
        $checkOut = new DateTimeImmutable('+4 days');

        $reservation = Reservation::create(
            ReservationId::generate(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            Uuid::uuid7()->toString(),
            new ReservationPeriod($checkIn, $checkOut),
        );

        $this->assertFalse($reservation->isWithinFreeCancellationWindow());
    }
}
