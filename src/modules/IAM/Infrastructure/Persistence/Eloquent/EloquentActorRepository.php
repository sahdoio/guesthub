<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\HashedPassword;
use Modules\IAM\Domain\ValueObject\RoleName;
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

        // Sync roles via pivot table
        $actorId = $this->model->newQuery()->where('uuid', $actor->uuid->value)->value('id');

        DB::table('actor_roles')->where('actor_id', $actorId)->delete();

        foreach ($actor->roles() as $role) {
            $roleId = RoleModel::where('uuid', $role->uuid->value)->value('id');
            DB::table('actor_roles')->insert([
                'actor_id' => $actorId,
                'role_id' => $roleId,
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

        $roles = $this->loadRoles($record->id);

        return $this->toEntity($record, $roles);
    }

    public function findByEmail(string $email): ?Actor
    {
        $record = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        if (! $record) {
            return null;
        }

        $roles = $this->loadRoles($record->id);

        return $this->toEntity($record, $roles);
    }

    public function nextIdentity(): ActorId
    {
        return ActorId::generate();
    }

    /** @return list<Role> */
    private function loadRoles(int $actorId): array
    {
        $roleRecords = DB::table('actor_roles')
            ->join('roles', 'roles.id', '=', 'actor_roles.role_id')
            ->where('actor_roles.actor_id', $actorId)
            ->select('roles.uuid', 'roles.name')
            ->get();

        return $roleRecords->map(fn ($r) => Role::create(
            uuid: RoleId::fromString($r->uuid),
            name: RoleName::from($r->name),
        ))->all();
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
            'subject_type' => $actor->subjectType,
            'subject_id' => $actor->subjectId,
            'created_at' => $actor->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $actor->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    /** @param list<Role> $roles */
    private function toEntity(object $record, array $roles): Actor
    {
        $accountId = $record->account_id !== null
            ? AccountId::fromString(
                AccountModel::where('id', $record->account_id)->value('uuid')
            )
            : null;

        return ActorReflector::reconstruct(
            uuid: ActorId::fromString($record->uuid),
            accountId: $accountId,
            roles: $roles,
            name: $record->name,
            email: $record->email,
            password: new HashedPassword($record->password),
            subjectType: $record->subject_type,
            subjectId: $record->subject_id !== null ? (int) $record->subject_id : null,
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }
}
