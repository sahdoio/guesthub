<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\Presenter;

use Modules\IAM\Domain\Actor;

final class ActorPresenter
{
    /**
     * @param  list<string>  $typeNames
     */
    public static function fromDomain(Actor $actor, array $typeNames = [], ?string $userUuid = null): array
    {
        return [
            'id' => (string) $actor->uuid,
            'roles' => $typeNames,
            'name' => $actor->name,
            'email' => $actor->email,
            'guest_id' => $userUuid,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
