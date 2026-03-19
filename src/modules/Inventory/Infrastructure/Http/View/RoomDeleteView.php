<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Inventory\Domain\Exception\RoomNotFoundException;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;

final class RoomDeleteView
{
    public function __construct(
        private RoomRepository $repository,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request, string $slug, string $id): RedirectResponse
    {
        $hotel = $this->hotelRepository->findBySlug($slug);

        abort_if($hotel === null, 404);

        $room = $this->repository->findByUuid(RoomId::fromString($id))
            ?? throw RoomNotFoundException::withId(RoomId::fromString($id));

        $this->repository->remove($room);

        return redirect("/hotels/{$slug}/rooms")->with('success', 'Room deleted.');
    }
}
