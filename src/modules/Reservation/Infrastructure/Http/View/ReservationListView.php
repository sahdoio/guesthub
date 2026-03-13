<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reservation\Application\Query\ListReservations;
use Modules\Reservation\Application\Query\ListReservationsHandler;
use Modules\Shared\Application\Query\Pagination;

final class ReservationListView
{
    public function __construct(
        private ListReservationsHandler $handler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $result = $this->handler->handle(
            new ListReservations(
                status: $request->query('status'),
                roomType: $request->query('room_type'),
            ),
            new Pagination(
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 15),
            ),
        );

        return Inertia::render('Reservations/Index', [
            'reservations' => $result->items,
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
            ],
            'filters' => [
                'status' => $request->query('status'),
                'room_type' => $request->query('room_type'),
            ],
        ]);
    }
}
