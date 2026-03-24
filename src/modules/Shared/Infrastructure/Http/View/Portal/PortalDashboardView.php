<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Domain\Repository\StayRepository;

final class PortalDashboardView
{
    public function __construct(
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $stayEntities = $this->stayRepository->findAll(limit: 8);

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
        ], $stayEntities);

        return Inertia::render('Portal/Dashboard', [
            'stays' => $stays,
        ]);
    }
}
