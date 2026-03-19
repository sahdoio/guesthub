<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\IAM\Presentation\Http\Presenter\HotelPresenter;
use Modules\Inventory\Application\Query\ListRooms;
use Modules\Inventory\Application\Query\ListRoomsHandler;
use Modules\Inventory\Presentation\Http\Presenter\RoomPresenter;
use Modules\Shared\Application\Query\Pagination;

final class RoomListView
{
    public function __construct(
        private ListRoomsHandler $handler,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $hotel = $this->hotelRepository->findBySlug($slug);

        abort_if($hotel === null, 404);

        $hotelNumericId = $this->hotelRepository->resolveNumericId($hotel->uuid);

        $result = $this->handler->handle(
            new ListRooms(
                status: $request->query('status'),
                type: $request->query('type'),
                floor: $request->query('floor') !== null ? (int) $request->query('floor') : null,
                hotelId: $hotelNumericId,
            ),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
            ),
        );

        return Inertia::render('Rooms/Index', [
            'hotel' => HotelPresenter::fromDomain($hotel),
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
