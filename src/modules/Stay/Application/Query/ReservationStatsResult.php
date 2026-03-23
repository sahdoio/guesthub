<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

final readonly class ReservationStatsResult
{
    /**
     * @param  array<string, int>  $byStatus
     */
    public function __construct(
        public int $total,
        public array $byStatus,
        public int $todayCheckIns,
        public int $todayCheckOuts,
    ) {}

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'by_status' => $this->byStatus,
            'today_check_ins' => $this->todayCheckIns,
            'today_check_outs' => $this->todayCheckOuts,
        ];
    }
}
