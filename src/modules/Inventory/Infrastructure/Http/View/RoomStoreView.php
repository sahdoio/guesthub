<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Application\Command\CreateRoom;
use Modules\Inventory\Application\Command\CreateRoomHandler;

final class RoomStoreView
{
    public function __construct(
        private CreateRoomHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:10', 'regex:/^\d{1,4}[A-Za-z]?$/'],
            'type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
            'floor' => ['required', 'integer', 'min:1', 'max:99'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'price_per_night' => ['required', 'numeric', 'min:0'],
            'amenities' => ['sometimes', 'array'],
            'amenities.*' => ['string', 'max:255'],
        ], [
            'number.regex' => 'Room number must be 1-4 digits optionally followed by a letter (e.g., 201, 101A).',
        ]);

        $id = $this->handler->handle(new CreateRoom(
            number: $data['number'],
            type: $data['type'],
            floor: (int) $data['floor'],
            capacity: (int) $data['capacity'],
            pricePerNight: (float) $data['price_per_night'],
            amenities: $data['amenities'] ?? [],
        ));

        return redirect("/rooms/{$id}")->with('success', 'Room created.');
    }
}
