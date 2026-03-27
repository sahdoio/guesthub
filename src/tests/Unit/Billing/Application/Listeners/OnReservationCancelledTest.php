<?php

declare(strict_types=1);

namespace Tests\Unit\Billing\Application\Listeners;

use DateTimeImmutable;
use Modules\Billing\Application\Command\CancelReservationBilling;
use Modules\Billing\Application\Command\CancelReservationBillingHandler;
use Modules\Billing\Application\Command\RefundInvoice;
use Modules\Billing\Application\Command\RefundInvoiceHandler;
use Modules\Billing\Application\Command\VoidInvoice;
use Modules\Billing\Application\Command\VoidInvoiceHandler;
use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\LineItem;
use Modules\Billing\Domain\LineItemId;
use Modules\Billing\Domain\PaymentId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;
use Modules\Billing\Domain\ValueObject\Money;
use Modules\Billing\Domain\ValueObject\PaymentMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(CancelReservationBillingHandler::class)]
final class OnReservationCancelledTest extends TestCase
{
    private function createInvoice(InvoiceStatus $targetStatus = InvoiceStatus::ISSUED): Invoice
    {
        $lineItem = LineItem::create(
            id: LineItemId::generate(),
            description: 'Room night',
            unitPrice: new Money(10000),
            quantity: 3,
        );

        $invoice = Invoice::createForReservation(
            uuid: InvoiceId::generate(),
            accountId: Uuid::uuid7()->toString(),
            reservationId: 'res-uuid-1',
            guestId: Uuid::uuid7()->toString(),
            lineItems: [$lineItem],
            taxRate: 0.10,
            createdAt: new DateTimeImmutable,
        );

        if ($targetStatus === InvoiceStatus::DRAFT) {
            return $invoice;
        }

        $invoice->issue();

        if ($targetStatus === InvoiceStatus::ISSUED) {
            return $invoice;
        }

        if ($targetStatus === InvoiceStatus::PAID) {
            $stripeId = 'pi_test_'.bin2hex(random_bytes(4));
            $invoice->recordPayment(
                paymentId: PaymentId::generate(),
                amount: $invoice->total,
                method: PaymentMethod::CARD,
                stripePaymentIntentId: $stripeId,
                createdAt: new DateTimeImmutable,
            );
            $invoice->markPaymentSucceeded($stripeId);
        }

        return $invoice;
    }

    #[Test]
    public function it_voids_issued_invoice_on_cancellation(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::ISSUED);

        $repository = $this->createMock(InvoiceRepository::class);
        $repository->expects($this->once())
            ->method('findByReservationId')
            ->with('res-uuid-1')
            ->willReturn($invoice);

        $voidHandler = $this->createMock(VoidInvoiceHandler::class);
        $voidHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (VoidInvoice $cmd) use ($invoice) {
                return $cmd->invoiceId === (string) $invoice->uuid
                    && str_contains($cmd->reason, 'Reservation cancelled');
            }));

        $refundHandler = $this->createMock(RefundInvoiceHandler::class);
        $refundHandler->expects($this->never())->method('handle');

        $handler = new CancelReservationBillingHandler($repository, $voidHandler, $refundHandler);

        $handler->handle(new CancelReservationBilling(
            reservationId: 'res-uuid-1',
            reason: 'Guest changed plans',
            freeCancellationUntil: null,
        ));
    }

    #[Test]
    public function it_voids_draft_invoice_on_cancellation(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::DRAFT);

        $repository = $this->createMock(InvoiceRepository::class);
        $repository->expects($this->once())
            ->method('findByReservationId')
            ->willReturn($invoice);

        $voidHandler = $this->createMock(VoidInvoiceHandler::class);
        $voidHandler->expects($this->once())->method('handle');

        $refundHandler = $this->createMock(RefundInvoiceHandler::class);
        $refundHandler->expects($this->never())->method('handle');

        $handler = new CancelReservationBillingHandler($repository, $voidHandler, $refundHandler);

        $handler->handle(new CancelReservationBilling(
            reservationId: 'res-uuid-1',
            reason: 'Changed mind',
            freeCancellationUntil: null,
        ));
    }

    #[Test]
    public function it_refunds_paid_invoice_within_free_cancellation_window(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::PAID);

        $repository = $this->createMock(InvoiceRepository::class);
        $repository->expects($this->once())
            ->method('findByReservationId')
            ->willReturn($invoice);

        $voidHandler = $this->createMock(VoidInvoiceHandler::class);
        $voidHandler->expects($this->never())->method('handle');

        $refundHandler = $this->createMock(RefundInvoiceHandler::class);
        $refundHandler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function (RefundInvoice $cmd) use ($invoice) {
                return $cmd->invoiceId === (string) $invoice->uuid;
            }));

        $handler = new CancelReservationBillingHandler($repository, $voidHandler, $refundHandler);

        $freeCancellationUntil = (new DateTimeImmutable('+7 days'))->format('c');

        $handler->handle(new CancelReservationBilling(
            reservationId: 'res-uuid-1',
            reason: 'Emergency',
            freeCancellationUntil: $freeCancellationUntil,
        ));
    }

    #[Test]
    public function it_does_not_refund_paid_invoice_past_free_cancellation_window(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::PAID);

        $repository = $this->createMock(InvoiceRepository::class);
        $repository->expects($this->once())
            ->method('findByReservationId')
            ->willReturn($invoice);

        $voidHandler = $this->createMock(VoidInvoiceHandler::class);
        $voidHandler->expects($this->never())->method('handle');

        $refundHandler = $this->createMock(RefundInvoiceHandler::class);
        $refundHandler->expects($this->never())->method('handle');

        $handler = new CancelReservationBillingHandler($repository, $voidHandler, $refundHandler);

        $freeCancellationUntil = (new DateTimeImmutable('-1 day'))->format('c');

        $handler->handle(new CancelReservationBilling(
            reservationId: 'res-uuid-1',
            reason: 'Too late',
            freeCancellationUntil: $freeCancellationUntil,
        ));
    }

    #[Test]
    public function it_does_nothing_when_no_invoice_exists(): void
    {
        $repository = $this->createMock(InvoiceRepository::class);
        $repository->expects($this->once())
            ->method('findByReservationId')
            ->willReturn(null);

        $voidHandler = $this->createMock(VoidInvoiceHandler::class);
        $voidHandler->expects($this->never())->method('handle');

        $refundHandler = $this->createMock(RefundInvoiceHandler::class);
        $refundHandler->expects($this->never())->method('handle');

        $handler = new CancelReservationBillingHandler($repository, $voidHandler, $refundHandler);

        $handler->handle(new CancelReservationBilling(
            reservationId: 'res-nonexistent',
            reason: 'No invoice',
            freeCancellationUntil: null,
        ));
    }
}
