<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Domain\Exception\RoomNotFoundException;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;

final class RoomDeleteView
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $room = $this->repository->findByUuid(RoomId::fromString($id))
            ?? throw RoomNotFoundException::withId(RoomId::fromString($id));

        $this->repository->remove($room);

        return redirect('/rooms')->with('success', 'Room deleted.');
    }
}
