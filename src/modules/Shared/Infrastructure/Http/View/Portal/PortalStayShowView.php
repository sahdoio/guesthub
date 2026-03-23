<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayImageModel;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

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

        $coverImageUrl = null;
        if ($stay->coverImagePath !== null) {
            $coverImageUrl = $disk->url($stay->coverImagePath);
        }

        $stayModel = StayModel::query()
            ->withoutGlobalScopes()
            ->where('uuid', $stay->uuid->value)
            ->first();

        $images = [];
        if ($stayModel) {
            $images = $stayModel->images->map(fn (StayImageModel $img) => [
                'id' => $img->uuid,
                'url' => $disk->url($img->path),
                'position' => $img->position,
            ])->all();
        }

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
