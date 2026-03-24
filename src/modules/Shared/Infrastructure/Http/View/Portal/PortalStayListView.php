<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Domain\Repository\StayRepository;

final class PortalStayListView
{
    public function __construct(
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $q = $request->query('q', '');
        $checkIn = $request->query('check_in', '');
        $checkOut = $request->query('check_out', '');
        $adults = (int) $request->query('adults', 1);
        $children = (int) $request->query('children', 0);
        $babies = (int) $request->query('babies', 0);
        $pets = (int) $request->query('pets', 0);
        $totalGuests = $adults + $children;
        $page = (int) $request->query('page', 1);
        $perPage = 12;

        $result = $this->stayRepository->findActivePaginated(
            page: $page,
            perPage: $perPage,
            search: $q ?: null,
            minCapacity: $totalGuests > 1 ? $totalGuests : null,
        );

        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        $stays = array_map(fn ($stay) => [
            'uuid' => (string) $stay->uuid,
            'name' => $stay->name,
            'slug' => $stay->slug,
            'description' => $stay->description,
            'address' => $stay->address,
            'type' => $stay->type->value,
            'category' => $stay->category->value,
            'price_per_night' => $stay->pricePerNight,
            'capacity' => $stay->capacity,
            'contact_email' => $stay->contactEmail,
            'contact_phone' => $stay->contactPhone,
            'cover_image_url' => $stay->coverImagePath ? $disk->url($stay->coverImagePath) : null,
        ], $result->items);

        return Inertia::render('Portal/Stays/Index', [
            'stays' => $stays,
            'meta' => [
                'current_page' => $result->currentPage,
                'last_page' => $result->lastPage,
                'total' => $result->total,
            ],
            'filters' => [
                'q' => $q,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
                'children' => $children,
                'babies' => $babies,
                'pets' => $pets,
            ],
        ]);
    }
}
