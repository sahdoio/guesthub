<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Query;

final readonly class BillingStatsResult
{
    /**
     * @param  array<string, int>  $byStatus
     */
    public function __construct(
        public int $total,
        public array $byStatus,
        public int $totalRevenueCents,
        public int $totalPendingCents,
        public string $currency,
    ) {}

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'by_status' => $this->byStatus,
            'total_revenue_cents' => $this->totalRevenueCents,
            'total_pending_cents' => $this->totalPendingCents,
            'currency' => $this->currency,
        ];
    }
}
