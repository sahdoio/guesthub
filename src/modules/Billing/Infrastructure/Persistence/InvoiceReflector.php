<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence;

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
use ReflectionClass;

/**
 * Reconstructs an Invoice aggregate from persisted data
 * bypassing constructor to avoid re-recording domain events.
 */
final class InvoiceReflector
{
    /**
     * @param  LineItem[]  $lineItems
     * @param  Payment[]   $payments
     */
    public static function reconstruct(
        InvoiceId $uuid,
        string $accountId,
        string $reservationId,
        string $guestId,
        InvoiceStatus $status,
        array $lineItems,
        array $payments,
        Money $subtotal,
        Money $tax,
        Money $total,
        ?string $stripeCustomerId,
        ?string $notes,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $issuedAt,
        ?DateTimeImmutable $paidAt,
        ?DateTimeImmutable $voidedAt,
        ?DateTimeImmutable $refundedAt,
    ): Invoice {
        $ref = new ReflectionClass(Invoice::class);
        $invoice = $ref->newInstanceWithoutConstructor();

        self::set($ref, $invoice, 'uuid', $uuid);
        self::set($ref, $invoice, 'accountId', $accountId);
        self::set($ref, $invoice, 'reservationId', $reservationId);
        self::set($ref, $invoice, 'guestId', $guestId);
        self::set($ref, $invoice, 'status', $status);
        self::set($ref, $invoice, 'lineItems', $lineItems);
        self::set($ref, $invoice, 'payments', $payments);
        self::set($ref, $invoice, 'subtotal', $subtotal);
        self::set($ref, $invoice, 'tax', $tax);
        self::set($ref, $invoice, 'total', $total);
        self::set($ref, $invoice, 'stripeCustomerId', $stripeCustomerId);
        self::set($ref, $invoice, 'notes', $notes);
        self::set($ref, $invoice, 'createdAt', $createdAt);
        self::set($ref, $invoice, 'issuedAt', $issuedAt);
        self::set($ref, $invoice, 'paidAt', $paidAt);
        self::set($ref, $invoice, 'voidedAt', $voidedAt);
        self::set($ref, $invoice, 'refundedAt', $refundedAt);

        return $invoice;
    }

    public static function reconstructLineItem(object $record): LineItem
    {
        $ref = new ReflectionClass(LineItem::class);
        $lineItem = $ref->newInstanceWithoutConstructor();

        self::set($ref, $lineItem, 'id', LineItemId::fromString($record->uuid));
        self::set($ref, $lineItem, 'description', $record->description);
        self::set($ref, $lineItem, 'unitPrice', new Money((int) $record->unit_price_cents));
        self::set($ref, $lineItem, 'quantity', (int) $record->quantity);
        self::set($ref, $lineItem, 'total', new Money((int) $record->total_cents));

        return $lineItem;
    }

    public static function reconstructPayment(object $record): Payment
    {
        $ref = new ReflectionClass(Payment::class);
        $payment = $ref->newInstanceWithoutConstructor();

        self::set($ref, $payment, 'id', PaymentId::fromString($record->uuid));
        self::set($ref, $payment, 'amount', new Money((int) $record->amount_cents, $record->currency));
        self::set($ref, $payment, 'status', PaymentStatus::from($record->status));
        self::set($ref, $payment, 'method', PaymentMethod::from($record->method));
        self::set($ref, $payment, 'stripePaymentIntentId', $record->stripe_payment_intent_id);
        self::set($ref, $payment, 'createdAt', new DateTimeImmutable($record->created_at));
        self::set($ref, $payment, 'succeededAt', $record->succeeded_at ? new DateTimeImmutable($record->succeeded_at) : null);
        self::set($ref, $payment, 'failedAt', $record->failed_at ? new DateTimeImmutable($record->failed_at) : null);
        self::set($ref, $payment, 'failureReason', $record->failure_reason);

        return $payment;
    }

    private static function set(ReflectionClass $ref, object $obj, string $prop, mixed $value): void
    {
        $property = $ref->getProperty($prop);
        $property->setValue($obj, $value);
    }
}
