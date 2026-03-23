<?php

declare(strict_types=1);

namespace Modules\Billing\Domain\Repository;

use Modules\Billing\Domain\Invoice;
use Modules\Billing\Domain\InvoiceId;

interface InvoiceRepository
{
    public function save(Invoice $invoice): void;

    public function findByUuid(InvoiceId $uuid): ?Invoice;

    public function findByReservationId(string $reservationId): ?Invoice;

    public function findByStripePaymentIntentId(string $stripePaymentIntentId): ?Invoice;

    public function nextIdentity(): InvoiceId;

    public function count(): int;

    public function countByStatus(): array;

    public function sumPaidTotals(): int;

    public function sumIssuedTotals(): int;
}
