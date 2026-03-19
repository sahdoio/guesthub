<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Query;

use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Shared\Application\Query\Pagination;
use Modules\Shared\Domain\PaginatedResult;

final readonly class ListRoomsHandler
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    /** @return PaginatedResult<\Modules\Inventory\Domain\Room> */
    public function handle(ListRooms $query, Pagination $pagination): PaginatedResult
    {
        return $this->repository->list(
            page: $pagination->page,
            perPage: $pagination->perPage,
            status: $query->status,
            type: $query->type,
            floor: $query->floor,
            hotelId: $query->hotelId,
        );
    }
}
