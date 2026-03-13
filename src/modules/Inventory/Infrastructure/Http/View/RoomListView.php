<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Application\Query\ListRooms;
use Modules\Inventory\Application\Query\ListRoomsHandler;
use Modules\Inventory\Presentation\Http\Presenter\RoomPresenter;
use Modules\Shared\Application\Query\Pagination;

final class RoomListView
{
    public function __construct(
        private ListRoomsHandler $handler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $result = $this->handler->handle(
            new ListRooms(
                status: $request->query('status'),
                type: $request->query('type'),
                floor: $request->query('floor') !== null ? (int) $request->query('floor') : null,
            ),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
            ),
        );

        return Inertia::render('Rooms/Index', [
            'rooms' => array_map(
                fn ($room) => RoomPresenter::fromDomain($room),
                $result->items,
            ),
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
            'filters' => [
                'status' => $request->query('status'),
                'type' => $request->query('type'),
                'floor' => $request->query('floor'),
            ],
        ]);
    }
}
