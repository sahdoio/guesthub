<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Presenter;

use Modules\IAM\Domain\Actor;

final class ActorPresenter
{
    /**
     * @param  list<string>  $roleNames
     */
    public static function fromDomain(Actor $actor, array $roleNames = [], ?string $guestUuid = null): array
    {
        return [
            'id' => (string) $actor->uuid,
            'roles' => $roleNames,
            'name' => $actor->name,
            'email' => $actor->email,
            'guest_id' => $guestUuid,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
