<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\InvoiceId;
use Modules\Billing\Domain\Repository\InvoiceRepository;
use Modules\Billing\Domain\ValueObject\InvoiceStatus;
use Modules\Billing\Domain\ValueObject\Money;
use Modules\Billing\Infrastructure\Persistence\InvoiceReflector;

final readonly class EloquentInvoiceRepository implements InvoiceRepository
{
    public function __construct(
        private InvoiceModel $model,
    ) {}

    public function save(Invoice $invoice, int $accountNumericId): void
    {
        $invoiceData = [
            'uuid' => $invoice->uuid->value,
            'account_id' => $accountNumericId,
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
        ];

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $invoiceData['uuid']], $invoiceData);

        $invoiceRecord = $this->model->newQuery()
            ->where('uuid', $invoice->uuid->value)
            ->first();

        $invoiceId = $invoiceRecord->id;

        // Sync line items
        LineItemModel::query()->where('invoice_id', $invoiceId)->delete();

        foreach ($invoice->lineItems as $lineItem) {
            LineItemModel::query()->insert([
                'uuid' => $lineItem->id->value,
                'invoice_id' => $invoiceId,
                'description' => $lineItem->description,
                'unit_price_cents' => $lineItem->unitPrice->amountInCents,
                'quantity' => $lineItem->quantity,
                'total_cents' => $lineItem->total->amountInCents,
                'created_at' => $invoice->createdAt->format('Y-m-d H:i:s'),
            ]);
        }

        // Sync payments
        PaymentModel::query()->where('invoice_id', $invoiceId)->delete();

        foreach ($invoice->payments as $payment) {
            PaymentModel::query()->insert([
                'uuid' => $payment->id->value,
                'invoice_id' => $invoiceId,
                'amount_cents' => $payment->amount->amountInCents,
                'currency' => $payment->amount->currency,
                'status' => $payment->status->value,
                'method' => $payment->method->value,
                'stripe_payment_intent_id' => $payment->stripePaymentIntentId,
                'failure_reason' => $payment->failureReason,
                'created_at' => $payment->createdAt->format('Y-m-d H:i:s'),
                'succeeded_at' => $payment->succeededAt?->format('Y-m-d H:i:s'),
                'failed_at' => $payment->failedAt?->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function findByUuid(InvoiceId $uuid): ?Invoice
    {
        $record = $this->model->newQuery()
            ->with(['lineItems', 'payments'])
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByReservationId(string $reservationId): ?Invoice
    {
        $record = $this->model->newQuery()
            ->with(['lineItems', 'payments'])
            ->where('reservation_id', $reservationId)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByStripePaymentIntentId(string $stripePaymentIntentId): ?Invoice
    {
        $payment = PaymentModel::query()
            ->where('stripe_payment_intent_id', $stripePaymentIntentId)
            ->first();

        if ($payment === null) {
            return null;
        }

        $record = $this->model->newQuery()
            ->with(['lineItems', 'payments'])
            ->where('id', $payment->invoice_id)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function nextIdentity(): InvoiceId
    {
        return InvoiceId::generate();
    }

    public function count(): int
    {
        return $this->model->newQuery()->count();
    }

    public function countByStatus(): array
    {
        return $this->model->newQuery()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function sumPaidTotals(): int
    {
        return (int) $this->model->newQuery()
            ->where('status', 'paid')
            ->sum('total_cents');
    }

    public function sumIssuedTotals(): int
    {
        return (int) $this->model->newQuery()
            ->where('status', 'issued')
            ->sum('total_cents');
    }

    public function findByUuidGlobal(InvoiceId $uuid): ?Invoice
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->with(['lineItems', 'payments'])
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByReservationIdGlobal(string $reservationId): ?Invoice
    {
        $record = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->with(['lineItems', 'payments'])
            ->where('reservation_id', $reservationId)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    /** @return Invoice[] */
    public function findAllByGuestIdGlobal(string $guestId): array
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->with(['lineItems', 'payments'])
            ->where('guest_id', $guestId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($record) => $this->toEntity($record))
            ->all();
    }

    public function resolveAccountNumericId(InvoiceId $uuid): ?int
    {
        $id = $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid->value)
            ->value('account_id');

        return $id !== null ? (int) $id : null;
    }

    public function listForOwnerView(int $page = 1, int $perPage = 15, ?string $status = null): array
    {
        $query = $this->model->newQuery()
            ->with(['lineItems', 'payments', 'reservation.stay', 'guest']);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $paginator = $query->orderByDesc('created_at')
            ->paginate(perPage: $perPage, page: $page);

        return [
            'items' => collect($paginator->items())->map(fn ($inv) => $inv->toArray())->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function findForOwnerView(string $uuid): ?array
    {
        $invoice = $this->model->newQuery()
            ->with(['lineItems', 'payments', 'reservation.stay', 'guest'])
            ->where('uuid', $uuid)
            ->first();

        if (! $invoice) {
            return null;
        }

        return array_merge($invoice->toArray(), [
            'line_items' => $invoice->lineItems->toArray(),
            'payments' => $invoice->payments->toArray(),
        ]);
    }

    public function hasProcessedStripeEvent(string $stripeEventId): bool
    {
        return StripeWebhookEventModel::query()
            ->where('stripe_event_id', $stripeEventId)
            ->exists();
    }

    public function recordStripeEvent(string $stripeEventId, string $eventType): void
    {
        StripeWebhookEventModel::query()->create([
            'stripe_event_id' => $stripeEventId,
            'event_type' => $eventType,
            'processed_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    private function toEntity(object $record): Invoice
    {
        $lineItems = collect($record->lineItems)
            ->map(fn (object $item) => InvoiceReflector::reconstructLineItem($item))
            ->all();

        $payments = collect($record->payments)
            ->map(fn (object $item) => InvoiceReflector::reconstructPayment($item))
            ->all();

        return InvoiceReflector::reconstruct(
            uuid: InvoiceId::fromString($record->uuid),
            accountId: $record->account_uuid ?? '',
            reservationId: $record->reservation_id,
            guestId: $record->guest_id,
            status: InvoiceStatus::from($record->status),
            lineItems: $lineItems,
            payments: $payments,
            subtotal: new Money((int) $record->subtotal_cents, $record->currency),
            tax: new Money((int) $record->tax_cents, $record->currency),
            total: new Money((int) $record->total_cents, $record->currency),
            stripeCustomerId: $record->stripe_customer_id,
            notes: $record->notes,
            createdAt: new DateTimeImmutable($record->created_at),
            issuedAt: $record->issued_at ? new DateTimeImmutable($record->issued_at) : null,
            paidAt: $record->paid_at ? new DateTimeImmutable($record->paid_at) : null,
            voidedAt: $record->voided_at ? new DateTimeImmutable($record->voided_at) : null,
            refundedAt: $record->refunded_at ? new DateTimeImmutable($record->refunded_at) : null,
        );
    }
}
