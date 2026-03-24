<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Query;

use JsonSerializable;
use Modules\Billing\Domain\Invoice;

final readonly class InvoiceReadModel implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $reservationId,
        public string $guestId,
        public string $status,
        public array $lineItems,
        public array $payments,
        public int $subtotalCents,
        public int $taxCents,
        public int $totalCents,
        public string $currency,
        public ?string $notes,
        public string $createdAt,
        public ?string $issuedAt,
        public ?string $paidAt,
    ) {}

    public static function fromDomain(Invoice $invoice): self
    {
        return new self(
            id: (string) $invoice->uuid,
            reservationId: $invoice->reservationId,
            guestId: $invoice->guestId,
            status: $invoice->status->value,
            lineItems: array_map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unitPrice->amountInCents,
                'total_cents' => $item->total->amountInCents,
            ], $invoice->lineItems),
            payments: array_map(fn ($payment) => [
                'stripe_payment_intent_id' => $payment->stripePaymentIntentId,
                'amount_cents' => $payment->amount->amountInCents,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'created_at' => $payment->createdAt->format('Y-m-d H:i:s'),
            ], $invoice->payments),
            subtotalCents: $invoice->subtotal->amountInCents,
            taxCents: $invoice->tax->amountInCents,
            totalCents: $invoice->total->amountInCents,
            currency: $invoice->total->currency,
            notes: $invoice->notes,
            createdAt: $invoice->createdAt->format('Y-m-d H:i:s'),
            issuedAt: $invoice->issuedAt?->format('Y-m-d H:i:s'),
            paidAt: $invoice->paidAt?->format('Y-m-d H:i:s'),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'reservation_id' => $this->reservationId,
            'guest_id' => $this->guestId,
            'status' => $this->status,
            'line_items' => $this->lineItems,
            'payments' => $this->payments,
            'subtotal_cents' => $this->subtotalCents,
            'tax_cents' => $this->taxCents,
            'total_cents' => $this->totalCents,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'timestamps' => [
                'created_at' => $this->createdAt,
                'issued_at' => $this->issuedAt,
                'paid_at' => $this->paidAt,
            ],
        ];
    }
}
