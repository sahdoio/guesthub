<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Query;

final readonly class RoomStatsResult
{
    /**
     * @param  array<string, int>  $byStatus
     * @param  array<string, int>  $byType
     */
    public function __construct(
        public int $total,
        public array $byStatus,
        public array $byType,
    ) {}

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'by_status' => $this->byStatus,
            'by_type' => $this->byType,
        ];
    }
}
