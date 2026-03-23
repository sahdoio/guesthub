<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

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
            $byType[$type->value] = StayModel::query()->where('type', $type->value)->count();
        }

        $byCategory = [];
        foreach (StayCategory::cases() as $category) {
            $byCategory[$category->value] = StayModel::query()->where('category', $category->value)->count();
        }

        return new StayStatsResult(
            total: $total,
            byStatus: $byStatus,
            byType: $byType,
            byCategory: $byCategory,
        );
    }
}
