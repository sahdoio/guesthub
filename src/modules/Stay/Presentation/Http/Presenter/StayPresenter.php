<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Presenter;

use Illuminate\Support\Facades\Storage;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayImageModel;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

final class StayPresenter
{
    /** @return array<string, mixed> */
    public static function fromDomain(Stay $stay): array
    {
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
