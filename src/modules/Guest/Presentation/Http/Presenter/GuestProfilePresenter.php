<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Presenter;

use Modules\Guest\Domain\GuestProfile;

final class GuestProfilePresenter
{
    public static function fromDomain(GuestProfile $profile): array
    {
        return [
            'id' => (string) $profile->uuid,
            'full_name' => $profile->fullName,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'document' => $profile->document,
            'loyalty_tier' => $profile->loyaltyTier->value,
            'preferences' => $profile->preferences,
            'created_at' => $profile->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $profile->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
