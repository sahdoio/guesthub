<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Query;

use Modules\Guest\Domain\Repository\GuestProfileRepository;

final readonly class GetGuestStatsHandler
{
    public function __construct(
        private GuestProfileRepository $repository,
    ) {}

    public function handle(GetGuestStats $query): GuestStatsResult
    {
        return new GuestStatsResult(
            total: $this->repository->count(),
            byLoyaltyTier: $this->repository->countByLoyaltyTier(),
        );
    }
}
