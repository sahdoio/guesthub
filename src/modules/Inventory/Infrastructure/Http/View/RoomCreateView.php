<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\IAM\Presentation\Http\Presenter\HotelPresenter;

final class RoomCreateView
{
    public function __construct(
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(string $slug): Response
    {
        $hotel = $this->hotelRepository->findBySlug($slug);

        abort_if($hotel === null, 404);

        return Inertia::render('Rooms/Create', [
            'hotel' => HotelPresenter::fromDomain($hotel),
        ]);
    }
}
