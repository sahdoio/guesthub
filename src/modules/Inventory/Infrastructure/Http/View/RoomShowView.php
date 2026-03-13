<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Domain\Exception\RoomNotFoundException;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Presentation\Http\Presenter\RoomPresenter;

final class RoomShowView
{
    public function __construct(
        private RoomRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $room = $this->repository->findByUuid(RoomId::fromString($id))
            ?? throw RoomNotFoundException::withId(RoomId::fromString($id));

        return Inertia::render('Rooms/Show', [
            'room' => RoomPresenter::fromDomain($room),
        ]);
    }
}
