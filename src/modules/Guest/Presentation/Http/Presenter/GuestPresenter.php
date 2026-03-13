<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Presenter;

use Modules\Guest\Domain\Guest;

final class GuestPresenter
{
    public static function fromDomain(Guest $guest): array
    {
        return [
            'id' => (string) $guest->uuid,
            'full_name' => $guest->fullName,
            'email' => $guest->email,
            'phone' => $guest->phone,
            'document' => $guest->document,
            'loyalty_tier' => $guest->loyaltyTier->value,
            'preferences' => $guest->preferences,
            'created_at' => $guest->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $guest->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
