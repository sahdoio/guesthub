<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Modules\IAM\Domain\Repository\RoleRepository;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\RoleName;

final class EloquentRoleRepository implements RoleRepository
{
    public function save(Role $role): void
    {
        RoleModel::query()->updateOrInsert(
            ['uuid' => $role->uuid->value],
            ['name' => $role->name->value],
        );
    }

    public function findById(RoleId $id): ?Role
    {
        $record = RoleModel::where('uuid', $id->value)->first();

        if (! $record) {
            return null;
        }

        return Role::create(
            uuid: RoleId::fromString($record->uuid),
            name: RoleName::from($record->name),
        );
    }

    public function findByName(RoleName $name): ?Role
    {
        $record = RoleModel::where('name', $name->value)->first();

        if (! $record) {
            return null;
        }

        return Role::create(
            uuid: RoleId::fromString($record->uuid),
            name: RoleName::from($record->name),
        );
    }

    public function nextIdentity(): RoleId
    {
        return RoleId::generate();
    }
}
