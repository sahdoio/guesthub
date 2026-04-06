<?php

declare(strict_types=1);

namespace Tests\Unit\Reservation\Infrastructure\Messaging;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationConfirmedEvent;
use Modules\Stay\Infrastructure\Messaging\IntegrationEventPublisher;
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
            stayId: 'stay-uuid-123',
            checkIn: '2026-02-01',
            checkOut: '2026-02-05',
            isVip: true,
        );

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event);

        $publisher = new IntegrationEventPublisher($dispatcher);
        $publisher->publish($event);
    }
}
