<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Query;

use Modules\Shared\Application\BaseData;

final readonly class UserStatsResult extends BaseData
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
