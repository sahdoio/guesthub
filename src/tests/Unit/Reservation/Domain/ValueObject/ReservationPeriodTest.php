<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReservationPeriod::class)]
final class ReservationPeriodTest extends TestCase
{
    #[Test]
    public function itCreatesAValidPeriod(): void
    {
        $checkIn = new DateTimeImmutable('+1 day');
        $checkOut = new DateTimeImmutable('+4 days');

        $period = new ReservationPeriod($checkIn, $checkOut);

        $this->assertSame($checkIn, $period->checkIn);
        $this->assertSame($checkOut, $period->checkOut);
    }

    #[Test]
    public function itCalculatesNights(): void
    {
        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+5 days'),
        );

        $this->assertSame(4, $period->nights());
    }

    #[Test]
    public function itRejectsCheckoutBeforeCheckin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Check-out must be after check-in');

        new ReservationPeriod(
            new DateTimeImmutable('+5 days'),
            new DateTimeImmutable('+2 days'),
        );
    }

    #[Test]
    public function itRejectsSameDayCheckout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $date = new DateTimeImmutable('+1 day');
        new ReservationPeriod($date, $date);
    }

    #[Test]
    public function itAllowsPastDatesForReconstruction(): void
    {
        $period = new ReservationPeriod(
            new DateTimeImmutable('-3 days'),
            new DateTimeImmutable('-1 day'),
        );

        $this->assertSame(2, $period->nights());
    }

    #[Test]
    public function itRejectsStayOver365Nights(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum stay is 365 nights');

        new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+367 days'),
        );
    }

    #[Test]
    public function itDetectsOverlappingPeriods(): void
    {
        $a = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+5 days'),
        );
        $b = new ReservationPeriod(
            new DateTimeImmutable('+3 days'),
            new DateTimeImmutable('+7 days'),
        );

        $this->assertTrue($a->overlaps($b));
        $this->assertTrue($b->overlaps($a));
    }

    #[Test]
    public function itDetectsNonOverlappingPeriods(): void
    {
        $a = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+3 days'),
        );
        $b = new ReservationPeriod(
            new DateTimeImmutable('+5 days'),
            new DateTimeImmutable('+7 days'),
        );

        $this->assertFalse($a->overlaps($b));
    }

    #[Test]
    public function itChecksIfDateIsContained(): void
    {
        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+5 days'),
        );

        $this->assertTrue($period->contains(new DateTimeImmutable('+2 days')));
        $this->assertFalse($period->contains(new DateTimeImmutable('+6 days')));
    }

    #[Test]
    public function itComparesEqualPeriods(): void
    {
        $checkIn = new DateTimeImmutable('+1 day');
        $checkOut = new DateTimeImmutable('+3 days');

        $a = new ReservationPeriod($checkIn, $checkOut);
        $b = new ReservationPeriod($checkIn, $checkOut);

        $this->assertTrue($a->equals($b));
    }
}
