<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Domain\Repository\StayRepository;

final class PortalStayShowView
{
    public function __construct(
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request, string $slug): Response
    {
        $stay = $this->stayRepository->findBySlug($slug);

        if (! $stay) {
            abort(404, 'Stay not found.');
        }

        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        $coverImageUrl = $stay->coverImagePath !== null
            ? $disk->url($stay->coverImagePath)
            : null;

        $images = array_map(fn (array $img) => [
            'id' => $img['uuid'],
            'url' => $disk->url($img['path']),
            'position' => $img['position'],
        ], $this->stayRepository->getImages($stay->uuid));

        return Inertia::render('Portal/Stays/Show', [
            'stay' => [
                'uuid' => (string) $stay->uuid,
                'account_uuid' => (string) $stay->accountId,
                'name' => $stay->name,
                'slug' => $stay->slug,
                'description' => $stay->description,
                'address' => $stay->address,
                'type' => $stay->type->value,
                'category' => $stay->category->value,
                'price_per_night' => $stay->pricePerNight,
                'capacity' => $stay->capacity,
                'amenities' => $stay->amenities,
                'contact_email' => $stay->contactEmail,
                'contact_phone' => $stay->contactPhone,
                'cover_image_url' => $coverImageUrl,
                'images' => $images,
            ],
        ]);
    }
}
