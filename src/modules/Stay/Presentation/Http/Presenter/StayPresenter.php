<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Presenter;

use Illuminate\Support\Facades\Storage;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Stay;

final class StayPresenter
{
    public function __construct(
        private StayRepository $stayRepository,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(Stay $stay): array
    {
        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        $coverImageUrl = $stay->coverImagePath !== null
            ? $disk->url($stay->coverImagePath)
            : null;

        $images = array_map(fn (array $img) => [
            'id' => $img['uuid'],
            'url' => $disk->url($img['path']),
            'position' => $img['position'],
        ], $this->stayRepository->getImages($stay->uuid));

        return [
            'id' => (string) $stay->uuid,
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
            'status' => $stay->status,
            'amenities' => $stay->amenities,
            'cover_image_url' => $coverImageUrl,
            'images' => $images,
            'created_at' => $stay->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $stay->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
