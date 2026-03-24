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
    public function it_rejects_checkin_in_the_past(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertFalse($spec->isSatisfiedBy(false, $period));
    }

    #[Test]
    public function it_allows_checkin_today(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('today'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertTrue($spec->isSatisfiedBy(false, $period));
    }

    #[Test]
    public function it_rejects_booking_too_far_in_advance_for_regular_guest(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertFalse($spec->isSatisfiedBy(false, $period));
    }

    #[Test]
    public function it_allows_vip_to_book_further_in_advance(): void
    {
        $spec = new ReservationCreationSpecification;

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertTrue($spec->isSatisfiedBy(true, $period));
    }
}
