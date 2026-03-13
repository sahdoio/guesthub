<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Query;

final readonly class ReservationStatsResult
{
    /**
     * @param array<string, int> $byStatus
     * @param array<string, int> $byRoomType
     */
    public function __construct(
        public int $total,
        public array $byStatus,
        public array $byRoomType,
        public int $todayCheckIns,
        public int $todayCheckOuts,
    ) {}

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'by_status' => $this->byStatus,
            'by_room_type' => $this->byRoomType,
            'today_check_ins' => $this->todayCheckIns,
            'today_check_outs' => $this->todayCheckOuts,
        ];
    }
}
