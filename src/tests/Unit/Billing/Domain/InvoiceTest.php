<?php

declare(strict_types=1);

namespace Tests\Unit\Billing\Domain;

use DateTimeImmutable;
use Modules\Billing\Domain\Event\InvoiceCreated;
use Modules\Billing\Domain\Event\InvoiceFullyPaid;
use Modules\Billing\Domain\Event\InvoiceIssued;
use Modules\Billing\Domain\Event\InvoiceRefunded;
use Modules\Billing\Domain\Event\InvoiceVoided;
use Modules\Billing\Domain\Event\PaymentRecorded;
use Modules\Billing\Domain\Exception\InvalidInvoiceStateException;
use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\LineItem;
use Modules\Billing\Domain\LineItemId;
use Modules\Billing\Domain\PaymentId;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;
use Modules\Billing\Domain\ValueObject\Money;
use Modules\Billing\Domain\ValueObject\PaymentMethod;
use Modules\Billing\Domain\ValueObject\PaymentStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[CoversClass(Invoice::class)]
final class InvoiceTest extends TestCase
{
    private function createLineItem(int $unitPriceCents = 10000, int $quantity = 2): LineItem
    {
        return LineItem::create(
            id: LineItemId::generate(),
            description: 'Room night',
            unitPrice: new Money($unitPriceCents),
            quantity: $quantity,
        );
    }

    private function createInvoice(array $lineItems = [], float $taxRate = 0.10): Invoice
    {
        if ($lineItems === []) {
            $lineItems = [$this->createLineItem()];
        }

        return Invoice::createForReservation(
            uuid: InvoiceId::generate(),
            accountId: Uuid::uuid7()->toString(),
            reservationId: Uuid::uuid7()->toString(),
            guestId: Uuid::uuid7()->toString(),
            lineItems: $lineItems,
            taxRate: $taxRate,
            createdAt: new DateTimeImmutable,
        );
    }

    #[Test]
    public function itCreatesADraftInvoice(): void
    {
        $lineItem = $this->createLineItem(10000, 2); // 100.00 * 2 = 200.00
        $invoice = $this->createInvoice([$lineItem], 0.10);

        $this->assertSame(InvoiceStatus::DRAFT, $invoice->status);
        $this->assertCount(1, $invoice->lineItems);
        $this->assertSame(20000, $invoice->subtotal->amountInCents); // 200.00
        $this->assertSame(2000, $invoice->tax->amountInCents);       // 20.00
        $this->assertSame(22000, $invoice->total->amountInCents);     // 220.00

        $events = $invoice->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InvoiceCreated::class, $events[0]);
    }

    #[Test]
    public function itIssuesADraftInvoice(): void
    {
        $invoice = $this->createInvoice();
        $invoice->pullDomainEvents();

        $invoice->issue();

        $this->assertSame(InvoiceStatus::ISSUED, $invoice->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $invoice->issuedAt);

        $events = $invoice->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InvoiceIssued::class, $events[0]);
    }

    #[Test]
    public function itRecordsAPayment(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue();
        $invoice->pullDomainEvents();

        $invoice->recordPayment(
            paymentId: PaymentId::generate(),
            amount: $invoice->total,
            method: PaymentMethod::CARD,
            stripePaymentIntentId: 'pi_test_123',
            createdAt: new DateTimeImmutable,
        );

        $this->assertCount(1, $invoice->payments);

        $events = $invoice->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PaymentRecorded::class, $events[0]);
    }

    #[Test]
    public function itMarksPaymentSucceededAndTransitionsToPaid(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue();

        $stripeId = 'pi_test_456';
        $invoice->recordPayment(
            paymentId: PaymentId::generate(),
            amount: $invoice->total,
            method: PaymentMethod::CARD,
            stripePaymentIntentId: $stripeId,
            createdAt: new DateTimeImmutable,
        );
        $invoice->pullDomainEvents();

        $invoice->markPaymentSucceeded($stripeId);

        $this->assertSame(InvoiceStatus::PAID, $invoice->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $invoice->paidAt);

        $events = $invoice->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InvoiceFullyPaid::class, $events[0]);
    }

    #[Test]
    public function itMarksPaymentFailed(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue();

        $stripeId = 'pi_test_789';
        $invoice->recordPayment(
            paymentId: PaymentId::generate(),
            amount: $invoice->total,
            method: PaymentMethod::CARD,
            stripePaymentIntentId: $stripeId,
            createdAt: new DateTimeImmutable,
        );

        $invoice->markPaymentFailed($stripeId, 'Card declined');

        $this->assertSame(PaymentStatus::FAILED, $invoice->payments[0]->status);
    }

    #[Test]
    public function itVoidsADraftInvoice(): void
    {
        $invoice = $this->createInvoice();
        $invoice->pullDomainEvents();

        $invoice->void('Created by mistake');

        $this->assertSame(InvoiceStatus::VOID, $invoice->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $invoice->voidedAt);

        $events = $invoice->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InvoiceVoided::class, $events[0]);
    }

    #[Test]
    public function itVoidsAnIssuedInvoice(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue();
        $invoice->pullDomainEvents();

        $invoice->void('Guest cancelled');

        $this->assertSame(InvoiceStatus::VOID, $invoice->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $invoice->voidedAt);

        $events = $invoice->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InvoiceVoided::class, $events[0]);
    }

    #[Test]
    public function itRefundsAPaidInvoice(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue();

        $stripeId = 'pi_test_refund';
        $invoice->recordPayment(
            paymentId: PaymentId::generate(),
            amount: $invoice->total,
            method: PaymentMethod::CARD,
            stripePaymentIntentId: $stripeId,
            createdAt: new DateTimeImmutable,
        );
        $invoice->markPaymentSucceeded($stripeId);
        $invoice->pullDomainEvents();

        $invoice->refund();

        $this->assertSame(InvoiceStatus::REFUNDED, $invoice->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $invoice->refundedAt);

        $events = $invoice->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InvoiceRefunded::class, $events[0]);
    }

    #[Test]
    public function itPreventsIssuingNonDraftInvoice(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue();

        $this->expectException(InvalidInvoiceStateException::class);
        $invoice->issue();
    }

    #[Test]
    public function itPreventsVoidingPaidInvoice(): void
    {
        $invoice = $this->createInvoice();
        $invoice->issue();

        $stripeId = 'pi_test_void';
        $invoice->recordPayment(
            paymentId: PaymentId::generate(),
            amount: $invoice->total,
            method: PaymentMethod::CARD,
            stripePaymentIntentId: $stripeId,
            createdAt: new DateTimeImmutable,
        );
        $invoice->markPaymentSucceeded($stripeId);

        $this->expectException(InvalidInvoiceStateException::class);
        $invoice->void('Too late');
    }

    #[Test]
    public function itPreventsRefundingNonPaidInvoice(): void
    {
        $invoice = $this->createInvoice();

        $this->expectException(InvalidInvoiceStateException::class);
        $invoice->refund();
    }

    #[Test]
    public function itCalculatesTotalsCorrectlyWithMultipleLineItems(): void
    {
        $lineItem1 = $this->createLineItem(10000, 2); // 100.00 * 2 = 200.00
        $lineItem2 = $this->createLineItem(5000, 3);  // 50.00  * 3 = 150.00

        $invoice = $this->createInvoice([$lineItem1, $lineItem2], 0.15);

        // subtotal = 200.00 + 150.00 = 350.00 = 35000 cents
        $this->assertSame(35000, $invoice->subtotal->amountInCents);
        // tax = 35000 * 0.15 = 5250 cents
        $this->assertSame(5250, $invoice->tax->amountInCents);
        // total = 35000 + 5250 = 40250 cents
        $this->assertSame(40250, $invoice->total->amountInCents);
    }
}
