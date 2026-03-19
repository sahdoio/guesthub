<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\HotelRepository;

final class PortalHotelListView
{
    public function __construct(
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $hotels = array_map(fn ($hotel) => [
            'uuid' => (string) $hotel->uuid,
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'description' => $hotel->description,
            'address' => $hotel->address,
            'contact_email' => $hotel->contactEmail,
            'contact_phone' => $hotel->contactPhone,
        ], $this->hotelRepository->findAll());

        return Inertia::render('Portal/Hotels/Index', [
            'hotels' => $hotels,
        ]);
    }
}
