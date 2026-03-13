<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\CheckInGuest;
use Modules\Reservation\Application\Command\CheckInGuestHandler;
use Modules\Reservation\Domain\Service\InventoryGateway;

final readonly class CheckInView
{
    public function __construct(
        private CheckInGuestHandler $handler,
        private InventoryGateway $inventoryGateway,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'regex:/^\d{1,4}[A-Za-z]?$/'],
        ], [
            'room_number.regex' => 'Room number must be 1-4 digits optionally followed by a letter (e.g., 201, 101A).',
        ]);

        if (!$this->inventoryGateway->isRoomAvailable($data['room_number'])) {
            return redirect("/reservations/{$id}")
                ->withErrors(['room_number' => 'The selected room is not available.']);
        }

        $this->handler->handle(new CheckInGuest(
            reservationId: $id,
            roomNumber: $data['room_number'],
        ));

        return redirect("/reservations/{$id}")->with('success', 'Guest checked in.');
    }
}
