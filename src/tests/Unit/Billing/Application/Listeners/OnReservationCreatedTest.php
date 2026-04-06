<?php

declare(strict_types=1);

namespace Tests\Unit\Billing\Application\Listeners;

use Modules\Billing\Application\Command\CreateInvoiceForReservation;
use Modules\Billing\Application\Command\CreateInvoiceForReservationHandler;
use Modules\Billing\Application\Command\IssueInvoice;
use Modules\Billing\Application\Command\IssueInvoiceHandler;
use Modules\Billing\Domain\DTO\ReservationInfo;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\Service\ReservationGateway;
use Modules\Billing\Infrastructure\Listeners\OnReservationCreated;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCreatedEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(OnReservationCreated::class)]
final class OnReservationCreatedTest extends TestCase
{
    #[Test]
    public function itCreatesAndIssuesInvoiceOnReservationCreated(): void
    {
        $reservationId = 'res-uuid-1';
        $accountId = 'acc-uuid-1';
        $guestId = 'guest-uuid-1';
        $stayName = 'Beach House';

        $reservationInfo = new ReservationInfo(
            reservationId: $reservationId,
            guestId: $guestId,
            stayId: 'stay-uuid-1',
            stayName: $stayName,
            accountId: $accountId,
            checkIn: '2026-04-01',
            checkOut: '2026-04-04',
            nights: 3,
            pricePerNight: 150.00,
        );

        $invoiceId = InvoiceId::generate();

        $gateway = $this->createMock(ReservationGateway::class);
        $gateway->expects($this->once())
            ->method('findReservation')
            ->with($reservationId)
            ->willReturn($reservationInfo);

        $createHandler = $this->createMock(CreateInvoiceForReservationHandler::class);
        $createHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (CreateInvoiceForReservation $cmd) use ($reservationId, $accountId, $guestId, $stayName) {
                return $cmd->reservationId === $reservationId
                    && $cmd->accountId === $accountId
                    && $cmd->guestId === $guestId
                    && $cmd->stayName === $stayName
                    && $cmd->pricePerNight === 150.00
                    && $cmd->nights === 3
                    && $cmd->checkIn === '2026-04-01'
                    && $cmd->checkOut === '2026-04-04';
            }))
            ->willReturn($invoiceId);

        $issueHandler = $this->createMock(IssueInvoiceHandler::class);
        $issueHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (IssueInvoice $cmd) use ($invoiceId) {
                return $cmd->invoiceId === (string) $invoiceId;
            }));

        $listener = new OnReservationCreated($gateway, $createHandler, $issueHandler);

        $event = new ReservationCreatedEvent(
            reservationId: $reservationId,
            guestEmail: 'guest@example.com',
            stayId: 'stay-uuid-1',
            checkIn: '2026-04-01',
            checkOut: '2026-04-04',
            isVip: false,
        );

        $listener->handle($event);
    }
}
