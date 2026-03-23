<?php

declare(strict_types=1);

namespace Tests\Unit\Billing\Infrastructure\IntegrationEvent;

use DateTimeImmutable;
use Modules\Billing\Infrastructure\IntegrationEvent\InvoiceFullyPaidEvent;
use Modules\Shared\Application\Messaging\IntegrationEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceFullyPaidEvent::class)]
final class InvoiceFullyPaidEventTest extends TestCase
{
    #[Test]
    public function it_implements_integration_event(): void
    {
        $event = new InvoiceFullyPaidEvent(
            invoiceId: 'inv-123',
            reservationId: 'res-456',
            occurredAt: new DateTimeImmutable(),
        );

        $this->assertInstanceOf(IntegrationEvent::class, $event);
    }

    #[Test]
    public function it_serializes_correctly(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20 14:00:00');
        $event = new InvoiceFullyPaidEvent(
            invoiceId: 'inv-123',
            reservationId: 'res-456',
            occurredAt: $occurredAt,
        );

        $array = $event->toArray();

        $this->assertSame('inv-123', $array['invoice_id']);
        $this->assertSame('res-456', $array['reservation_id']);
        $this->assertSame($occurredAt->format('c'), $array['occurred_at']);
    }

    #[Test]
    public function it_returns_occurred_at(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20 14:00:00');
        $event = new InvoiceFullyPaidEvent(
            invoiceId: 'inv-123',
            reservationId: 'res-456',
            occurredAt: $occurredAt,
        );

        $this->assertSame($occurredAt, $event->occurredAt());
    }
}
