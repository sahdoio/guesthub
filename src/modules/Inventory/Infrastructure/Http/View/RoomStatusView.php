<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Application\Command\ChangeRoomStatus;
use Modules\Inventory\Application\Command\ChangeRoomStatusHandler;

final class RoomStatusView
{
    public function __construct(
        private ChangeRoomStatusHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:available,occupied,maintenance,out_of_order'],
        ]);

        $this->handler->handle(new ChangeRoomStatus(
            roomId: $id,
            status: $data['status'],
        ));

        return redirect("/rooms/{$id}")->with('success', 'Room status updated.');
    }
}
