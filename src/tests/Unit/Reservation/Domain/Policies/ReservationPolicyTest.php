<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Domain\Policies;

use DateTimeImmutable;
use Modules\Reservation\Domain\Dto\RoomAvailability;
use Modules\Reservation\Domain\Policies\ReservationPolicy;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReservationPolicyTest extends TestCase
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
    public function it_rejects_checkin_in_the_past(): void
    {
        $policy = new ReservationPolicy($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertFalse($policy->canCreateReservation(false, $period, 'SINGLE'));
    }

    #[Test]
    public function it_allows_checkin_today(): void
    {
        $policy = new ReservationPolicy($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('today'),
            new DateTimeImmutable('+2 days'),
        );

        $this->assertTrue($policy->canCreateReservation(false, $period, 'SINGLE'));
    }

    #[Test]
    public function it_rejects_booking_too_far_in_advance_for_regular_guest(): void
    {
        $policy = new ReservationPolicy($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertFalse($policy->canCreateReservation(false, $period, 'SINGLE'));
    }

    #[Test]
    public function it_allows_vip_to_book_further_in_advance(): void
    {
        $policy = new ReservationPolicy($this->inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+61 days'),
            new DateTimeImmutable('+65 days'),
        );

        $this->assertTrue($policy->canCreateReservation(true, $period, 'SINGLE'));
    }

    #[Test]
    public function it_rejects_when_no_rooms_available(): void
    {
        $inventory = $this->createStub(InventoryGateway::class);
        $inventory->method('checkAvailability')->willReturn(
            new RoomAvailability('SINGLE', 0, 150.00),
        );

        $policy = new ReservationPolicy($inventory);

        $period = new ReservationPeriod(
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+3 days'),
        );

        $this->assertFalse($policy->canCreateReservation(false, $period, 'SINGLE'));
    }
}
