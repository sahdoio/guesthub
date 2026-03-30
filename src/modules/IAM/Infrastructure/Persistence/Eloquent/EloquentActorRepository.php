<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\ValueObject\AccountId;
use Modules\IAM\Domain\ValueObject\ActorId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Domain\ValueObject\TypeId;
use Modules\IAM\Infrastructure\Persistence\ActorReflector;

final class EloquentActorRepository implements ActorRepository
{
    public function __construct(
        private readonly ActorModel $model,
    ) {}

    public function save(Actor $actor): void
    {
        $data = $this->toRecord($actor);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);

        // Sync types via pivot table
        $actorId = $this->model->newQuery()->where('uuid', $actor->uuid->value)->value('id');

        DB::table('actor_type_map')->where('actor_id', $actorId)->delete();

        foreach ($actor->typeIds() as $typeId) {
            $dbTypeId = ActorTypeModel::where('uuid', $typeId->value)->value('id');
            DB::table('actor_type_map')->insert([
                'actor_id' => $actorId,
                'type_id' => $dbTypeId,
            ]);
        }
    }

    public function findByUuid(ActorId $uuid): ?Actor
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        if (! $record) {
            return null;
        }

        $typeIds = $this->loadTypeIds($record->id);

        return $this->toEntity($record, $typeIds);
    }

    public function findByEmail(string $email): ?Actor
    {
        $record = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        if (! $record) {
            return null;
        }

        $typeIds = $this->loadTypeIds($record->id);

        return $this->toEntity($record, $typeIds);
    }

    public function findByNumericId(int $id): ?Actor
    {
        $record = $this->model->newQuery()
            ->where('id', $id)
            ->first();

        if (! $record) {
            return null;
        }

        $typeIds = $this->loadTypeIds($record->id);

        return $this->toEntity($record, $typeIds);
    }

    /** @return list<array{id: int, uuid: string, name: string, email: string, type_names: list<string>}> */
    public function findActorsByAccountId(int $accountId): array
    {
        return $this->model->newQuery()
            ->where('account_id', $accountId)
            ->with('types')
            ->get()
            ->map(fn (ActorModel $actor) => [
                'id' => $actor->id,
                'uuid' => $actor->uuid,
                'name' => $actor->name,
                'email' => $actor->email,
                'type_names' => $actor->types->pluck('name')->values()->toArray(),
            ])
            ->all();
    }

    /** @return list<string> */
    public function resolveTypeNames(ActorId $uuid): array
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        if (! $record) {
            return [];
        }

        return DB::table('actor_type_map')
            ->join('actor_types', 'actor_types.id', '=', 'actor_type_map.type_id')
            ->where('actor_type_map.actor_id', $record->id)
            ->pluck('actor_types.name')
            ->all();
    }

    public function nextIdentity(): ActorId
    {
        return ActorId::generate();
    }

    /** @return list<TypeId> */
    private function loadTypeIds(int $actorId): array
    {
        return DB::table('actor_type_map')
            ->join('actor_types', 'actor_types.id', '=', 'actor_type_map.type_id')
            ->where('actor_type_map.actor_id', $actorId)
            ->pluck('actor_types.uuid')
            ->map(fn (string $uuid) => TypeId::fromString($uuid))
            ->all();
    }

    private function toRecord(Actor $actor): array
    {
        $accountId = $actor->accountId !== null
            ? AccountModel::where('uuid', $actor->accountId->value)->value('id')
            : null;

        return [
            'uuid' => $actor->uuid->value,
            'account_id' => $accountId,
            'name' => $actor->name,
            'email' => $actor->email,
            'password' => $actor->password->value,
            'user_id' => $actor->userId,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /** @param list<TypeId> $typeIds */
    private function toEntity(object $record, array $typeIds): Actor
    {
        $accountId = $record->account_id !== null
            ? AccountId::fromString(
                AccountModel::where('id', $record->account_id)->value('uuid')
            )
            : null;

        return ActorReflector::reconstruct(
            uuid: ActorId::fromString($record->uuid),
            accountId: $accountId,
            typeIds: $typeIds,
            name: $record->name,
            email: $record->email,
            password: new HashedPassword($record->password),
            userId: $record->user_id !== null ? (int) $record->user_id : null,
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }
}
