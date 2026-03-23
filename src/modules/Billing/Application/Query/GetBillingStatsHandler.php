<?php

declare(strict_types=1);

namespace Modules\Billing\Application\Query;

use Modules\Billing\Domain\Repository\InvoiceRepository;

final class GetBillingStatsHandler
{
    public function __construct(
        private readonly InvoiceRepository $repository,
    ) {}

    public function handle(GetBillingStats $query): BillingStatsResult
    {
        return new BillingStatsResult(
            total: $this->repository->count(),
            byStatus: $this->repository->countByStatus(),
            totalRevenueCents: $this->repository->sumPaidTotals(),
            totalPendingCents: $this->repository->sumIssuedTotals(),
            currency: 'USD',
        );
    }
}
