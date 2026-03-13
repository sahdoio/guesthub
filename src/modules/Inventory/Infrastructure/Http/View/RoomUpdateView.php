<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Application\Command\UpdateRoom;
use Modules\Inventory\Application\Command\UpdateRoomHandler;

final class RoomUpdateView
{
    public function __construct(
        private UpdateRoomHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'price_per_night' => ['sometimes', 'numeric', 'min:0'],
            'amenities' => ['sometimes', 'array'],
            'amenities.*' => ['string', 'max:255'],
        ]);

        $this->handler->handle(new UpdateRoom(
            roomId: $id,
            pricePerNight: isset($data['price_per_night']) ? (float) $data['price_per_night'] : null,
            amenities: $data['amenities'] ?? null,
        ));

        return redirect("/rooms/{$id}")->with('success', 'Room updated.');
    }
}
