<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Guest\Domain\GuestProfile;

/** @mixin GuestProfile */
final class GuestProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var GuestProfile $profile */
        $profile = $this->resource;

        return [
            'id' => (string) $profile->uuid(),
            'full_name' => $profile->fullName(),
            'email' => $profile->email(),
            'phone' => $profile->phone(),
            'document' => $profile->document(),
            'loyalty_tier' => $profile->loyaltyTier()->value,
            'preferences' => $profile->preferences(),
            'created_at' => $profile->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $profile->updatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
