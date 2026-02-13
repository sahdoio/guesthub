<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Reservation\Infrastructure\IntegrationEvent\GuestCheckedInEvent;
use Modules\Reservation\Infrastructure\IntegrationEvent\GuestCheckedOutEvent;
use Modules\Reservation\Infrastructure\IntegrationEvent\ReservationCancelledEvent;
use Modules\Reservation\Infrastructure\IntegrationEvent\ReservationConfirmedEvent;
use Modules\Shared\Application\Messaging\IntegrationEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReservationConfirmedEvent::class)]
#[CoversClass(ReservationCancelledEvent::class)]
#[CoversClass(GuestCheckedInEvent::class)]
#[CoversClass(GuestCheckedOutEvent::class)]
final class IntegrationEventTest extends TestCase
{
    #[Test]
    public function reservationConfirmedEventSerializesCorrectly(): void
    {
        $occurredAt = new DateTimeImmutable('2026-01-15 10:00:00');
        $event = new ReservationConfirmedEvent(
            reservationId: 'res-123',
            guestEmail: 'alice@hotel.com',
            roomType: 'SUITE',
            checkIn: '2026-02-01',
            checkOut: '2026-02-05',
            isVip: true,
            occurredAt: $occurredAt,
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);
        $this->assertSame($occurredAt, $event->occurredAt());

        $array = $event->toArray();
        $this->assertSame('res-123', $array['reservation_id']);
        $this->assertSame('alice@hotel.com', $array['guest_email']);
        $this->assertSame('SUITE', $array['room_type']);
        $this->assertSame('2026-02-01', $array['check_in']);
        $this->assertSame('2026-02-05', $array['check_out']);
        $this->assertTrue($array['is_vip']);
        $this->assertSame($occurredAt->format('c'), $array['occurred_at']);
    }

    #[Test]
    public function reservationCancelledEventSerializesCorrectly(): void
    {
        $occurredAt = new DateTimeImmutable('2026-01-15 12:00:00');
        $event = new ReservationCancelledEvent(
            reservationId: 'res-456',
            roomType: 'DOUBLE',
            checkIn: '2026-03-01',
            checkOut: '2026-03-04',
            reason: 'Plans changed',
            occurredAt: $occurredAt,
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);

        $array = $event->toArray();
        $this->assertSame('res-456', $array['reservation_id']);
        $this->assertSame('DOUBLE', $array['room_type']);
        $this->assertSame('Plans changed', $array['reason']);
    }

    #[Test]
    public function guestCheckedInEventSerializesCorrectly(): void
    {
        $occurredAt = new DateTimeImmutable('2026-02-01 14:00:00');
        $event = new GuestCheckedInEvent(
            reservationId: 'res-789',
            roomNumber: '301',
            guestEmail: 'bob@hotel.com',
            isVip: false,
            occurredAt: $occurredAt,
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);

        $array = $event->toArray();
        $this->assertSame('res-789', $array['reservation_id']);
        $this->assertSame('301', $array['room_number']);
        $this->assertSame('bob@hotel.com', $array['guest_email']);
        $this->assertFalse($array['is_vip']);
    }

    #[Test]
    public function guestCheckedOutEventSerializesCorrectly(): void
    {
        $occurredAt = new DateTimeImmutable('2026-02-05 11:00:00');
        $event = new GuestCheckedOutEvent(
            reservationId: 'res-789',
            roomNumber: '301',
            guestEmail: 'bob@hotel.com',
            occurredAt: $occurredAt,
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);

        $array = $event->toArray();
        $this->assertSame('res-789', $array['reservation_id']);
        $this->assertSame('301', $array['room_number']);
        $this->assertSame('bob@hotel.com', $array['guest_email']);
        $this->assertArrayNotHasKey('is_vip', $array);
    }
}
