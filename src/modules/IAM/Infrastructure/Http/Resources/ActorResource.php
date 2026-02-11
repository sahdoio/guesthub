<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\IAM\Domain\Actor;

/** @mixin Actor */
final class ActorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Actor $actor */
        $actor = $this->resource;

        $guestProfileUuid = $actor->guestProfileId
            ? DB::table('guest_profiles')->where('id', $actor->guestProfileId)->value('uuid')
            : null;

        return [
            'id' => (string) $actor->uuid,
            'type' => $actor->type->value,
            'name' => $actor->name,
            'email' => $actor->email,
            'guest_profile_id' => $guestProfileUuid,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
