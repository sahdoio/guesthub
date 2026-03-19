<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\IAM\Presentation\Http\Presenter\HotelPresenter;
use Modules\Inventory\Domain\Exception\RoomNotFoundException;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Presentation\Http\Presenter\RoomPresenter;

final class RoomEditView
{
    public function __construct(
        private RoomRepository $repository,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request, string $slug, string $id): Response
    {
        $hotel = $this->hotelRepository->findBySlug($slug);

        abort_if($hotel === null, 404);

        $room = $this->repository->findByUuid(RoomId::fromString($id))
            ?? throw RoomNotFoundException::withId(RoomId::fromString($id));

        return Inertia::render('Rooms/Edit', [
            'hotel' => HotelPresenter::fromDomain($hotel),
            'room' => RoomPresenter::fromDomain($room),
        ]);
    }
}
