<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\Presenter;

use Modules\IAM\Domain\User;

final class UserPresenter
{
    public static function fromDomain(User $user): array
    {
        return [
            'id' => (string) $user->uuid,
            'full_name' => $user->fullName,
            'email' => $user->email,
            'phone' => $user->phone,
            'document' => $user->document,
            'loyalty_tier' => $user->loyaltyTier?->value,
            'preferences' => $user->preferences,
            'created_at' => $user->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $user->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
