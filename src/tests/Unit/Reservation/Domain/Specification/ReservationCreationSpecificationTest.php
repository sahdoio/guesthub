<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\Specification;

use DateTimeImmutable;
use Modules\Stay\Domain\Specification\ReservationCreationSpecification;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReservationCreationSpecification::class)]
final class ReservationCreationSpecificationTest extends TestCase
{
    #[Test]
    public function itRejectsCheckinInThePast(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertFalse($spec->isSatisfiedBy(false, $period));
    }

    #[Test]
    public function itAllowsCheckinToday(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('today'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertTrue($spec->isSatisfiedBy(false, $period));
    }

    #[Test]
    public function itRejectsBookingTooFarInAdvanceForRegularGuest(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertFalse($spec->isSatisfiedBy(false, $period));
    }

    #[Test]
    public function itAllowsVipToBookFurtherInAdvance(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertTrue($spec->isSatisfiedBy(true, $period));
    }
}
