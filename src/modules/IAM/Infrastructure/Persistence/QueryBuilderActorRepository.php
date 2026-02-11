<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\ValueObject\ActorType;
use Modules\IAM\Domain\ValueObject\HashedPassword;

final class QueryBuilderActorRepository implements ActorRepository
{
    private const string TABLE = 'actors';

    public function save(Actor $actor): void
    {
        $data = $this->toRecord($actor);

        $existing = DB::table(self::TABLE)
            ->where('uuid', $actor->uuid()->value)
            ->first();

        if ($existing) {
            DB::table(self::TABLE)
                ->where('id', $existing->id)
                ->update($data);
        } else {
            DB::table(self::TABLE)->insert($data);
        }
    }

    public function findByUuid(ActorId $uuid): ?Actor
    {
        $record = DB::table(self::TABLE)
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByEmail(string $email): ?Actor
    {
        $record = DB::table(self::TABLE)
            ->where('email', $email)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function nextIdentity(): ActorId
    {
        return ActorId::generate();
    }

    private function toRecord(Actor $actor): array
    {
        return [
            'uuid' => $actor->uuid()->value,
            'type' => $actor->type()->value,
            'name' => $actor->name(),
            'email' => $actor->email(),
            'password' => $actor->password()->value,
            'guest_profile_id' => $actor->guestProfileId(),
            'created_at' => $actor->createdAt()->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    private function toEntity(object $record): Actor
    {
        return ActorReflector::reconstruct(
            uuid: ActorId::fromString($record->uuid),
            type: ActorType::from($record->type),
            name: $record->name,
            email: $record->email,
            password: new HashedPassword($record->password),
            guestProfileId: $record->guest_profile_id,
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }
}
