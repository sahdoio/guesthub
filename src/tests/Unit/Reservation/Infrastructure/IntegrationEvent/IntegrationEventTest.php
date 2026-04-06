<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Shared\Infrastructure\Messaging\IntegrationEvent;
use Modules\Stay\Infrastructure\IntegrationEvent\GuestCheckedInEvent;
use Modules\Stay\Infrastructure\IntegrationEvent\GuestCheckedOutEvent;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCancelledEvent;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationConfirmedEvent;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCreatedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReservationCreatedEvent::class)]
#[CoversClass(ReservationConfirmedEvent::class)]
#[CoversClass(ReservationCancelledEvent::class)]
#[CoversClass(GuestCheckedInEvent::class)]
#[CoversClass(GuestCheckedOutEvent::class)]
final class IntegrationEventTest extends TestCase
{
    #[Test]
    public function reservationCreatedEventSerializesCorrectly(): void
    {
        $event = new ReservationCreatedEvent(
            reservationId: 'res-100',
            guestEmail: 'guest@example.com',
            stayId: 'stay-uuid-100',
            checkIn: '2026-02-01',
            checkOut: '2026-02-05',
            isVip: false,
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);
        $this->assertInstanceOf(DateTimeImmutable::class, $event->occurredAt);

        $array = $event->toArray();
        $this->assertSame('res-100', $array['reservation_id']);
        $this->assertSame('guest@example.com', $array['guest_email']);
        $this->assertSame('stay-uuid-100', $array['stay_id']);
        $this->assertSame('2026-02-01', $array['check_in']);
        $this->assertSame('2026-02-05', $array['check_out']);
        $this->assertFalse($array['is_vip']);
        $this->assertSame($event->occurredAt->format('c'), $array['occurred_at']);
    }

    #[Test]
    public function reservationConfirmedEventSerializesCorrectly(): void
    {
        $event = new ReservationConfirmedEvent(
            reservationId: 'res-123',
            guestEmail: 'alice@hotel.com',
            stayId: 'stay-uuid-123',
            checkIn: '2026-02-01',
            checkOut: '2026-02-05',
            isVip: true,
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);
        $this->assertInstanceOf(DateTimeImmutable::class, $event->occurredAt);

        $array = $event->toArray();
        $this->assertSame('res-123', $array['reservation_id']);
        $this->assertSame('alice@hotel.com', $array['guest_email']);
        $this->assertSame('stay-uuid-123', $array['stay_id']);
        $this->assertSame('2026-02-01', $array['check_in']);
        $this->assertSame('2026-02-05', $array['check_out']);
        $this->assertTrue($array['is_vip']);
        $this->assertSame($event->occurredAt->format('c'), $array['occurred_at']);
    }

    #[Test]
    public function reservationCancelledEventSerializesCorrectly(): void
    {
        $event = new ReservationCancelledEvent(
            reservationId: 'res-456',
            stayId: 'stay-uuid-456',
            checkIn: '2026-03-01',
            checkOut: '2026-03-04',
            reason: 'Plans changed',
            freeCancellationUntil: '2026-02-27T00:00:00+00:00',
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);

        $array = $event->toArray();
        $this->assertSame('res-456', $array['reservation_id']);
        $this->assertSame('stay-uuid-456', $array['stay_id']);
        $this->assertSame('Plans changed', $array['reason']);
    }

    #[Test]
    public function guestCheckedInEventSerializesCorrectly(): void
    {
        $event = new GuestCheckedInEvent(
            reservationId: 'res-789',
            guestEmail: 'bob@hotel.com',
            isVip: false,
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);

        $array = $event->toArray();
        $this->assertSame('res-789', $array['reservation_id']);
        $this->assertSame('bob@hotel.com', $array['guest_email']);
        $this->assertFalse($array['is_vip']);
    }

    #[Test]
    public function guestCheckedOutEventSerializesCorrectly(): void
    {
        $event = new GuestCheckedOutEvent(
            reservationId: 'res-789',
            guestEmail: 'bob@hotel.com',
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);

        $array = $event->toArray();
        $this->assertSame('res-789', $array['reservation_id']);
        $this->assertSame('bob@hotel.com', $array['guest_email']);
        $this->assertArrayNotHasKey('is_vip', $array);
    }
}
