<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;

final readonly class GetStayStatsHandler
{
    public function __construct(
        private StayRepository $repository,
    ) {}

    public function handle(GetStayStats $query): StayStatsResult
    {
        $total = $this->repository->count();

        $byStatus = [
            'active' => $this->repository->countByStatus('active'),
            'inactive' => $this->repository->countByStatus('inactive'),
        ];

        $byType = [];
        foreach (StayType::cases() as $type) {
            $byType[$type->value] = $this->repository->countByType($type->value);
        }

        $byCategory = [];
        foreach (StayCategory::cases() as $category) {
            $byCategory[$category->value] = $this->repository->countByCategory($category->value);
        }

        return new StayStatsResult(
            total: $total,
            byStatus: $byStatus,
            byType: $byType,
            byCategory: $byCategory,
        );
    }
}
