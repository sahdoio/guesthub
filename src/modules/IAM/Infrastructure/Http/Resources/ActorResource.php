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
    private const array PROFILE_TABLE_MAP = [
        'guest' => 'guest_profiles',
    ];

    public function toArray(Request $request): array
    {
        /** @var Actor $actor */
        $actor = $this->resource;

        return [
            'id' => (string) $actor->uuid,
            'type' => $actor->type->value,
            'name' => $actor->name,
            'email' => $actor->email,
            'profile_type' => $actor->profileType,
            'profile_id' => $this->resolveProfileUuid($actor->profileType, $actor->profileId),
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function resolveProfileUuid(?string $profileType, ?int $profileId): ?string
    {
        if ($profileType === null || $profileId === null) {
            return null;
        }

        $table = self::PROFILE_TABLE_MAP[$profileType] ?? null;

        if ($table === null) {
            return null;
        }

        return DB::table($table)->where('id', $profileId)->value('uuid');
    }
}
