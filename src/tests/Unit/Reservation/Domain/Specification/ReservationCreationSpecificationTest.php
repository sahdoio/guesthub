<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\Specification;

use DateTimeImmutable;
use Modules\Reservation\Domain\Dto\RoomAvailability;
use Modules\Reservation\Domain\Specification\ReservationCreationSpecification;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReservationCreationSpecification::class)]
final class ReservationCreationSpecificationTest extends TestCase
{
    private InventoryGateway $inventory;

    protected function setUp(): void
    {
        $this->inventory = $this->createStub(InventoryGateway::class);
        $this->inventory->method('checkAvailability')->willReturn(
            new RoomAvailability('SINGLE', 10, 150.00),
        );
    }

    #[Test]
    public function itRejectsCheckinInThePast(): void
    {
        $spec = new ReservationCreationSpecification($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertFalse($spec->isSatisfiedBy(false, $period, 'SINGLE'));
    }

    #[Test]
    public function itAllowsCheckinToday(): void
    {
        $spec = new ReservationCreationSpecification($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('today'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertTrue($spec->isSatisfiedBy(false, $period, 'SINGLE'));
    }

    #[Test]
    public function itRejectsBookingTooFarInAdvanceForRegularGuest(): void
    {
        $spec = new ReservationCreationSpecification($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertFalse($spec->isSatisfiedBy(false, $period, 'SINGLE'));
    }

    #[Test]
    public function itAllowsVipToBookFurtherInAdvance(): void
    {
        $spec = new ReservationCreationSpecification($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertTrue($spec->isSatisfiedBy(true, $period, 'SINGLE'));
    }

    #[Test]
    public function itRejectsWhenNoRoomsAvailable(): void
    {
        $inventory = $this->createStub(InventoryGateway::class);
        $inventory->method('checkAvailability')->willReturn(
            new RoomAvailability('SINGLE', 0, 150.00),
        );

        $spec = new ReservationCreationSpecification($inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+3 days'),
        );

        $this->assertFalse($spec->isSatisfiedBy(false, $period, 'SINGLE'));
    }
}
