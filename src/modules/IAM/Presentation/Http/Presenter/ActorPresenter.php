<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Presenter;

use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\Role;

final class ActorPresenter
{
    public static function fromDomain(Actor $actor): array
    {
        $guestUuid = ($actor->subjectType === 'guest' && $actor->subjectId !== null)
            ? GuestModel::where('id', $actor->subjectId)->value('uuid')
            : null;

        return [
            'id' => (string) $actor->uuid,
            'roles' => array_map(fn (Role $r) => $r->name->value, $actor->roles()),
            'name' => $actor->name,
            'email' => $actor->email,
            'guest_id' => $guestUuid,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
