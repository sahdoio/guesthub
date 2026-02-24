<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Presenter;

use Modules\IAM\Domain\Actor;

final class ActorPresenter
{
    public static function fromDomain(Actor $actor): array
    {
        return [
            'id' => (string) $actor->uuid,
            'type' => $actor->type->value,
            'name' => $actor->name,
            'email' => $actor->email,
            'profile_type' => $actor->profileType,
            'profile_id' => $actor->profileId,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
