<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Presenter;

use Modules\IAM\Domain\Hotel;

final class HotelPresenter
{
    /** @return array<string, mixed> */
    public static function fromDomain(Hotel $hotel): array
    {
        return [
            'id' => (string) $hotel->uuid,
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'description' => $hotel->description,
            'address' => $hotel->address,
            'contact_email' => $hotel->contactEmail,
            'contact_phone' => $hotel->contactPhone,
            'status' => $hotel->status,
            'created_at' => $hotel->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $hotel->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
