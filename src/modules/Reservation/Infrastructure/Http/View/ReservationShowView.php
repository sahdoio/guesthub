<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;
use Modules\Reservation\Domain\Service\InventoryGateway;

final class ReservationShowView
{
    public function __construct(
        private GetReservationHandler $handler,
        private InventoryGateway $inventoryGateway,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $reservation = $this->handler->handle(new GetReservation($id));

        $availableRooms = [];
        if ($reservation->status === 'confirmed') {
            $availableRooms = array_map(
                fn ($room) => [
                    'number' => $room->number,
                    'floor' => $room->floor,
                    'price_per_night' => $room->pricePerNight,
                ],
                $this->inventoryGateway->listAvailableRooms($reservation->roomType),
            );
        }

        return Inertia::render('Reservations/Show', [
            'reservation' => $reservation,
            'availableRooms' => $availableRooms,
        ]);
    }
}
