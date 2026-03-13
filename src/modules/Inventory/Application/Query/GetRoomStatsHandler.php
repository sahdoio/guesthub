<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Query;

use Modules\Inventory\Domain\Repository\RoomRepository;

final readonly class GetRoomStatsHandler
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    public function handle(GetRoomStats $query): RoomStatsResult
    {
        return new RoomStatsResult(
            total: $this->repository->count(),
            byStatus: $this->repository->countByStatus(),
            byType: $this->repository->countByType(),
        );
    }
}
