<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\Messaging;

use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Reservation\Infrastructure\IntegrationEvent\ReservationConfirmedEvent;
use Modules\Reservation\Infrastructure\Messaging\IntegrationEventPublisher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(IntegrationEventPublisher::class)]
final class IntegrationEventPublisherTest extends TestCase
{
    #[Test]
    public function itDispatchesTheIntegrationEvent(): void
    {
        Log::shouldReceive('info')->once();

        $event = new ReservationConfirmedEvent(
            reservationId: 'res-123',
            guestEmail: 'alice@hotel.com',
            roomType: 'SUITE',
            checkIn: '2026-02-01',
            checkOut: '2026-02-05',
            isVip: true,
            occurredAt: new DateTimeImmutable(),
        );

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $publisher = new IntegrationEventPublisher($dispatcher);
        $publisher->publish($event);
    }
}
