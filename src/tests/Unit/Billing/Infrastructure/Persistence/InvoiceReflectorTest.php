<?php

declare(strict_types=1);

namespace Tests\Unit\Billing\Infrastructure\Persistence;

use DateTimeImmutable;
use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\LineItem;
use Modules\Billing\Domain\LineItemId;
use Modules\Billing\Domain\Payment;
use Modules\Billing\Domain\PaymentId;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;
use Modules\Billing\Domain\ValueObject\Money;
use Modules\Billing\Domain\ValueObject\PaymentMethod;
use Modules\Billing\Domain\ValueObject\PaymentStatus;
use Modules\Billing\Infrastructure\Persistence\InvoiceReflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceReflector::class)]
final class InvoiceReflectorTest extends TestCase
{
    #[Test]
    public function it_reconstructs_a_draft_invoice(): void
    {
        $uuid = InvoiceId::generate();
        $createdAt = new DateTimeImmutable('2026-03-15 10:00:00');
        $subtotal = new Money(20000);
        $tax = new Money(2000);
        $total = new Money(22000);

        $lineItem = InvoiceReflector::reconstructLineItem((object) [
            'uuid' => (string) LineItemId::generate(),
            'description' => 'Room night',
            'unit_price_cents' => 10000,
            'quantity' => 2,
            'total_cents' => 20000,
        ]);

        $invoice = InvoiceReflector::reconstruct(
            uuid: $uuid,
            accountId: 'account-uuid-123',
            reservationId: 'reservation-uuid-123',
            guestId: 'guest-uuid-123',
            status: InvoiceStatus::DRAFT,
            lineItems: [$lineItem],
            payments: [],
            subtotal: $subtotal,
            tax: $tax,
            total: $total,
            stripeCustomerId: null,
            notes: null,
            createdAt: $createdAt,
            issuedAt: null,
            paidAt: null,
            voidedAt: null,
            refundedAt: null,
        );

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertTrue($uuid->equals($invoice->uuid));
        $this->assertSame('account-uuid-123', $invoice->accountId);
        $this->assertSame('reservation-uuid-123', $invoice->reservationId);
        $this->assertSame('guest-uuid-123', $invoice->guestId);
        $this->assertSame(InvoiceStatus::DRAFT, $invoice->status);
        $this->assertCount(1, $invoice->lineItems);
        $this->assertEmpty($invoice->payments);
        $this->assertSame(20000, $invoice->subtotal->amountInCents);
        $this->assertSame(2000, $invoice->tax->amountInCents);
        $this->assertSame(22000, $invoice->total->amountInCents);
        $this->assertNull($invoice->stripeCustomerId);
        $this->assertNull($invoice->notes);
        $this->assertSame($createdAt, $invoice->createdAt);
        $this->assertNull($invoice->issuedAt);
        $this->assertNull($invoice->paidAt);
        $this->assertNull($invoice->voidedAt);
        $this->assertNull($invoice->refundedAt);
    }

    #[Test]
    public function it_reconstructs_a_paid_invoice_with_payment(): void
    {
        $issuedAt = new DateTimeImmutable('2026-03-16');
        $paidAt = new DateTimeImmutable('2026-03-17');

        $payment = InvoiceReflector::reconstructPayment((object) [
            'uuid' => (string) PaymentId::generate(),
            'amount_cents' => 22000,
            'currency' => 'usd',
            'status' => 'succeeded',
            'method' => 'card',
            'stripe_payment_intent_id' => 'pi_test_123',
            'created_at' => '2026-03-16 12:00:00',
            'succeeded_at' => '2026-03-16 12:01:00',
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        $invoice = InvoiceReflector::reconstruct(
            uuid: InvoiceId::generate(),
            accountId: 'account-uuid-456',
            reservationId: 'reservation-uuid-456',
            guestId: 'guest-uuid-456',
            status: InvoiceStatus::PAID,
            lineItems: [],
            payments: [$payment],
            subtotal: new Money(20000),
            tax: new Money(2000),
            total: new Money(22000),
            stripeCustomerId: 'cus_test_123',
            notes: null,
            createdAt: new DateTimeImmutable('2026-03-15'),
            issuedAt: $issuedAt,
            paidAt: $paidAt,
            voidedAt: null,
            refundedAt: null,
        );

        $this->assertSame(InvoiceStatus::PAID, $invoice->status);
        $this->assertSame($issuedAt, $invoice->issuedAt);
        $this->assertSame($paidAt, $invoice->paidAt);
        $this->assertSame('cus_test_123', $invoice->stripeCustomerId);
        $this->assertCount(1, $invoice->payments);
        $this->assertSame(PaymentStatus::SUCCEEDED, $invoice->payments[0]->status);
        $this->assertSame('pi_test_123', $invoice->payments[0]->stripePaymentIntentId);
    }

    #[Test]
    public function it_reconstructs_a_voided_invoice(): void
    {
        $voidedAt = new DateTimeImmutable('2026-03-18');

        $invoice = InvoiceReflector::reconstruct(
            uuid: InvoiceId::generate(),
            accountId: 'account-uuid-789',
            reservationId: 'reservation-uuid-789',
            guestId: 'guest-uuid-789',
            status: InvoiceStatus::VOID,
            lineItems: [],
            payments: [],
            subtotal: new Money(10000),
            tax: new Money(1000),
            total: new Money(11000),
            stripeCustomerId: null,
            notes: 'Created by mistake',
            createdAt: new DateTimeImmutable('2026-03-15'),
            issuedAt: null,
            paidAt: null,
            voidedAt: $voidedAt,
            refundedAt: null,
        );

        $this->assertSame(InvoiceStatus::VOID, $invoice->status);
        $this->assertSame('Created by mistake', $invoice->notes);
        $this->assertSame($voidedAt, $invoice->voidedAt);
    }

    #[Test]
    public function it_reconstructs_line_item_correctly(): void
    {
        $lineItemUuid = (string) LineItemId::generate();

        $lineItem = InvoiceReflector::reconstructLineItem((object) [
            'uuid' => $lineItemUuid,
            'description' => 'Minibar',
            'unit_price_cents' => 500,
            'quantity' => 3,
            'total_cents' => 1500,
        ]);

        $this->assertInstanceOf(LineItem::class, $lineItem);
        $this->assertSame('Minibar', $lineItem->description);
        $this->assertSame(500, $lineItem->unitPrice->amountInCents);
        $this->assertSame(3, $lineItem->quantity);
        $this->assertSame(1500, $lineItem->total->amountInCents);
    }

    #[Test]
    public function it_reconstructs_payment_correctly(): void
    {
        $paymentUuid = (string) PaymentId::generate();

        $payment = InvoiceReflector::reconstructPayment((object) [
            'uuid' => $paymentUuid,
            'amount_cents' => 22000,
            'currency' => 'usd',
            'status' => 'pending',
            'method' => 'card',
            'stripe_payment_intent_id' => 'pi_test_456',
            'created_at' => '2026-03-16 12:00:00',
            'succeeded_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertSame(22000, $payment->amount->amountInCents);
        $this->assertSame('usd', $payment->amount->currency);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);
        $this->assertSame(PaymentMethod::CARD, $payment->method);
        $this->assertSame('pi_test_456', $payment->stripePaymentIntentId);
    }

    #[Test]
    public function it_does_not_record_domain_events(): void
    {
        $invoice = InvoiceReflector::reconstruct(
            uuid: InvoiceId::generate(),
            accountId: 'account-uuid',
            reservationId: 'reservation-uuid',
            guestId: 'guest-uuid',
            status: InvoiceStatus::DRAFT,
            lineItems: [],
            payments: [],
            subtotal: Money::zero(),
            tax: Money::zero(),
            total: Money::zero(),
            stripeCustomerId: null,
            notes: null,
            createdAt: new DateTimeImmutable,
            issuedAt: null,
            paidAt: null,
            voidedAt: null,
            refundedAt: null,
        );

        $this->assertEmpty($invoice->pullDomainEvents());
    }
}
