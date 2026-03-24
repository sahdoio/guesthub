<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Http\Presenter;

use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\LineItem;
use Modules\Billing\Domain\Payment;

final class InvoicePresenter
{
    /** @return array<string, mixed> */
    public static function toArray(Invoice $invoice): array
    {
        return [
            'uuid' => $invoice->uuid->value,
            'account_uuid' => $invoice->accountId,
            'reservation_id' => $invoice->reservationId,
            'guest_id' => $invoice->guestId,
            'status' => $invoice->status->value,
            'subtotal_cents' => $invoice->subtotal->amountInCents,
            'tax_cents' => $invoice->tax->amountInCents,
            'total_cents' => $invoice->total->amountInCents,
            'currency' => $invoice->total->currency,
            'stripe_customer_id' => $invoice->stripeCustomerId,
            'notes' => $invoice->notes,
            'created_at' => $invoice->createdAt->format('Y-m-d H:i:s'),
            'issued_at' => $invoice->issuedAt?->format('Y-m-d H:i:s'),
            'paid_at' => $invoice->paidAt?->format('Y-m-d H:i:s'),
            'voided_at' => $invoice->voidedAt?->format('Y-m-d H:i:s'),
            'refunded_at' => $invoice->refundedAt?->format('Y-m-d H:i:s'),
            'line_items' => array_map(self::lineItemToArray(...), $invoice->lineItems),
            'payments' => array_map(self::paymentToArray(...), $invoice->payments),
        ];
    }

    /** @return array<string, mixed> */
    private static function lineItemToArray(LineItem $item): array
    {
        return [
            'uuid' => $item->id->value,
            'description' => $item->description,
            'unit_price_cents' => $item->unitPrice->amountInCents,
            'quantity' => $item->quantity,
            'total_cents' => $item->total->amountInCents,
        ];
    }

    /** @return array<string, mixed> */
    private static function paymentToArray(Payment $payment): array
    {
        return [
            'uuid' => $payment->id->value,
            'amount_cents' => $payment->amount->amountInCents,
            'currency' => $payment->amount->currency,
            'status' => $payment->status->value,
            'method' => $payment->method->value,
            'stripe_payment_intent_id' => $payment->stripePaymentIntentId,
            'failure_reason' => $payment->failureReason,
            'created_at' => $payment->createdAt->format('Y-m-d H:i:s'),
            'succeeded_at' => $payment->succeededAt?->format('Y-m-d H:i:s'),
            'failed_at' => $payment->failedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
