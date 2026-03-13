<?php

declare(strict_types=1);

namespace Modules\Guest\Application\Query;

final readonly class GuestStatsResult
{
    /**
     * @param  array<string, int>  $byLoyaltyTier
     */
    public function __construct(
        public int $total,
        public array $byLoyaltyTier,
    ) {}

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'by_loyalty_tier' => $this->byLoyaltyTier,
        ];
    }
}
