<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

final readonly class StayStatsResult
{
    /**
     * @param  array<string, int>  $byStatus
     * @param  array<string, int>  $byType
     * @param  array<string, int>  $byCategory
     */
    public function __construct(
        public int $total,
        public array $byStatus,
        public array $byType,
        public array $byCategory,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'by_status' => $this->byStatus,
            'by_type' => $this->byType,
            'by_category' => $this->byCategory,
        ];
    }
}
