<?php

declare(strict_types=1);

namespace Modules\Billing\Domain;

use DateTimeImmutable;
use Modules\Billing\Domain\Event\InvoiceCreated;
use Modules\Billing\Domain\Event\InvoiceFullyPaid;
use Modules\Billing\Domain\Event\InvoiceIssued;
use Modules\Billing\Domain\Event\InvoiceRefunded;
use Modules\Billing\Domain\Event\InvoiceVoided;
use Modules\Billing\Domain\Event\PaymentRecorded;
use Modules\Billing\Domain\Exception\InvalidInvoiceStateException;
use Modules\Billing\Domain\Exception\PaymentNotFoundException;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;
use Modules\Billing\Domain\ValueObject\Money;
use Modules\Billing\Domain\ValueObject\PaymentMethod;
use Modules\Billing\Domain\ValueObject\PaymentStatus;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Invoice extends AggregateRoot
{
    /**
     * @param  LineItem[]  $lineItems
     * @param  Payment[]  $payments
     */
    private function __construct(
        public readonly InvoiceId $uuid,
        public readonly string $accountId,
        public readonly string $reservationId,
        public readonly string $guestId,
        private(set) InvoiceStatus $status,
        private(set) array $lineItems,
        private(set) array $payments,
        private(set) Money $subtotal,
        private(set) Money $tax,
        private(set) Money $total,
        private(set) ?string $stripeCustomerId,
        private(set) ?string $notes,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $issuedAt,
        private(set) ?DateTimeImmutable $paidAt,
        private(set) ?DateTimeImmutable $voidedAt,
        private(set) ?DateTimeImmutable $refundedAt,
    ) {}

    /**
     * @param  LineItem[]  $lineItems
     */
    public static function createForReservation(
        InvoiceId $uuid,
        string $accountId,
        string $reservationId,
        string $guestId,
        array $lineItems,
        float $taxRate,
        DateTimeImmutable $createdAt,
    ): self {
        $subtotal = Money::zero();
        foreach ($lineItems as $lineItem) {
            $subtotal = $subtotal->add($lineItem->total);
        }

        $taxAmountInCents = (int) round($subtotal->amountInCents * $taxRate);
        $tax = new Money($taxAmountInCents, $subtotal->currency);
        $total = $subtotal->add($tax);

        $invoice = new self(
            uuid: $uuid,
            accountId: $accountId,
            reservationId: $reservationId,
            guestId: $guestId,
            status: InvoiceStatus::DRAFT,
            lineItems: $lineItems,
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

        $invoice->recordEvent(new InvoiceCreated($uuid, $reservationId));

        return $invoice;
    }

    public function id(): Identity
    {
        return $this->uuid;
    }

    public function issue(): void
    {
        if ($this->status !== InvoiceStatus::DRAFT) {
            throw InvalidInvoiceStateException::forTransition($this->status, InvoiceStatus::ISSUED);
        }

        $this->status = InvoiceStatus::ISSUED;
        $this->issuedAt = new DateTimeImmutable;

        $this->recordEvent(new InvoiceIssued($this->uuid));
    }

    public function recordPayment(
        PaymentId $paymentId,
        Money $amount,
        PaymentMethod $method,
        ?string $stripePaymentIntentId,
        DateTimeImmutable $createdAt,
    ): void {
        $payment = Payment::create(
            id: $paymentId,
            amount: $amount,
            method: $method,
            stripePaymentIntentId: $stripePaymentIntentId,
            createdAt: $createdAt,
        );

        $this->payments[] = $payment;

        $this->recordEvent(new PaymentRecorded($this->uuid, $paymentId));
    }

    public function markPaymentSucceeded(string $stripePaymentIntentId): void
    {
        $payment = $this->findPaymentByStripeId($stripePaymentIntentId);
        $payment->markSucceeded();

        $succeededTotal = $this->calculateSucceededPayments();

        if ($succeededTotal->amountInCents >= $this->total->amountInCents) {
            $this->status = InvoiceStatus::PAID;
            $this->paidAt = new DateTimeImmutable;

            $this->recordEvent(new InvoiceFullyPaid($this->uuid, $this->reservationId));
        }
    }

    public function markPaymentFailed(string $stripePaymentIntentId, string $reason): void
    {
        $payment = $this->findPaymentByStripeId($stripePaymentIntentId);
        $payment->markFailed($reason);
    }

    public function void(string $reason): void
    {
        if (! in_array($this->status, [InvoiceStatus::DRAFT, InvoiceStatus::ISSUED], true)) {
            throw InvalidInvoiceStateException::forTransition($this->status, InvoiceStatus::VOID);
        }

        $this->status = InvoiceStatus::VOID;
        $this->notes = $reason;
        $this->voidedAt = new DateTimeImmutable;

        $this->recordEvent(new InvoiceVoided($this->uuid, $reason));
    }

    public function refund(): void
    {
        if ($this->status !== InvoiceStatus::PAID) {
            throw InvalidInvoiceStateException::forTransition($this->status, InvoiceStatus::REFUNDED);
        }

        $this->status = InvoiceStatus::REFUNDED;
        $this->refundedAt = new DateTimeImmutable;

        $this->recordEvent(new InvoiceRefunded($this->uuid));
    }

    public function setStripeCustomerId(string $stripeCustomerId): void
    {
        $this->stripeCustomerId = $stripeCustomerId;
    }

    private function findPaymentByStripeId(string $stripePaymentIntentId): Payment
    {
        foreach ($this->payments as $payment) {
            if ($payment->stripePaymentIntentId === $stripePaymentIntentId) {
                return $payment;
            }
        }

        throw PaymentNotFoundException::withId($stripePaymentIntentId);
    }

    private function calculateSucceededPayments(): Money
    {
        $total = Money::zero($this->total->currency);

        foreach ($this->payments as $payment) {
            if ($payment->status === PaymentStatus::SUCCEEDED) {
                $total = $total->add($payment->amount);
            }
        }

        return $total;
    }
}
