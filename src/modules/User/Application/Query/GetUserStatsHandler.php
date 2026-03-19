<?php

declare(strict_types=1);

namespace Modules\User\Application\Query;

use Modules\User\Domain\Repository\UserRepository;

final readonly class GetUserStatsHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function handle(GetUserStats $query): UserStatsResult
    {
        return new UserStatsResult(
            total: $this->repository->count(),
            byLoyaltyTier: $this->repository->countByLoyaltyTier(),
        );
    }
}
