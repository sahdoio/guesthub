<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use Modules\Stay\Domain\Repository\ReservationRepository;

final class GetReservationStatsHandler
{
    public function __construct(
        private readonly ReservationRepository $repository,
    ) {}

    public function handle(GetReservationStats $query): ReservationStatsResult
    {
        return new ReservationStatsResult(
            total: $this->repository->count(),
            byStatus: $this->repository->countByStatus(),
            todayCheckIns: $this->repository->countTodayCheckIns(),
            todayCheckOuts: $this->repository->countTodayCheckOuts(),
        );
    }
}
