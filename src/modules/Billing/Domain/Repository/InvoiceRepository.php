<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Repository;

use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\InvoiceId;

interface InvoiceRepository
{
    public function save(Invoice $invoice, int $accountNumericId): void;

    public function findByUuid(InvoiceId $uuid): ?Invoice;

    public function findByUuidGlobal(InvoiceId $uuid): ?Invoice;

    public function findByReservationId(string $reservationId): ?Invoice;

    public function findByReservationIdGlobal(string $reservationId): ?Invoice;

    public function findByStripePaymentIntentId(string $stripePaymentIntentId): ?Invoice;

    /** @return Invoice[] */
    public function findAllByGuestIdGlobal(string $guestId): array;

    public function resolveAccountNumericId(InvoiceId $uuid): ?int;

    public function nextIdentity(): InvoiceId;

    public function count(): int;

    public function countByStatus(): array;

    public function sumPaidTotals(): int;

    public function sumIssuedTotals(): int;

    /** @return array{items: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int}} */
    public function listForOwnerView(int $page = 1, int $perPage = 15, ?string $status = null): array;

    /** @return array<string, mixed>|null */
    public function findForOwnerView(string $uuid): ?array;

    public function hasProcessedStripeEvent(string $stripeEventId): bool;

    public function recordStripeEvent(string $stripeEventId, string $eventType): void;
}
