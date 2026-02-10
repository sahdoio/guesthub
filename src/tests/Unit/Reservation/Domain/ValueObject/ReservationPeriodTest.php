<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReservationPeriodTest extends TestCase
{
    #[Test]
    public function it_creates_a_valid_period(): void
    {
        $checkIn = new DateTimeImmutable('+1 day');
        $checkOut = new DateTimeImmutable('+4 days');

        $period = new ReservationPeriod($checkIn, $checkOut);

        $this->assertSame($checkIn, $period->checkIn);
        $this->assertSame($checkOut, $period->checkOut);
    }

    #[Test]
    public function it_calculates_nights(): void
    {
        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+5 days'),
        );

        $this->assertSame(4, $period->nights());
    }

    #[Test]
    public function it_rejects_checkout_before_checkin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Check-out must be after check-in');

        new ReservationPeriod(
            new DateTimeImmutable('+5 days'),
            new DateTimeImmutable('+2 days'),
        );
    }

    #[Test]
    public function it_rejects_same_day_checkout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $date = new DateTimeImmutable('+1 day');
        new ReservationPeriod($date, $date);
    }

    #[Test]
    public function it_rejects_checkin_in_the_past(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Check-in cannot be in the past');

        new ReservationPeriod(
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+2 days'),
        );
    }

    #[Test]
    public function it_rejects_stay_over_365_nights(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum stay is 365 nights');

        new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+367 days'),
        );
    }

    #[Test]
    public function it_detects_overlapping_periods(): void
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
    public function it_detects_non_overlapping_periods(): void
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
    public function it_checks_if_date_is_contained(): void
    {
        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+5 days'),
        );

        $this->assertTrue($period->contains(new DateTimeImmutable('+2 days')));
        $this->assertFalse($period->contains(new DateTimeImmutable('+6 days')));
    }

    #[Test]
    public function it_compares_equal_periods(): void
    {
        $checkIn = new DateTimeImmutable('+1 day');
        $checkOut = new DateTimeImmutable('+3 days');

        $a = new ReservationPeriod($checkIn, $checkOut);
        $b = new ReservationPeriod($checkIn, $checkOut);

        $this->assertTrue($a->equals($b));
    }
}
