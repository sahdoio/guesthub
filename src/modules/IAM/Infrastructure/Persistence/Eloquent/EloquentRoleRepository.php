<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\Repository\RoleRepository;
use Modules\IAM\Domain\ValueObject\RoleName;
use Modules\IAM\Infrastructure\Persistence\RoleReflector;

final class EloquentRoleRepository implements RoleRepository
{
    public function __construct(
        private readonly RoleModel $model,
    ) {}

    public function save(Role $role): void
    {
        $data = [
            'uuid' => $role->uuid->value,
            'name' => $role->name->value,
        ];

        $this->model->newQuery()->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(RoleId $uuid): ?Role
    {
        $record = $this->model->newQuery()->where('uuid', $uuid->value)->first();

        return $record ? RoleReflector::reconstruct(
            uuid: RoleId::fromString($record->uuid),
            name: RoleName::from($record->name),
        ) : null;
    }

    public function findByName(RoleName $name): ?Role
    {
        $record = $this->model->newQuery()->where('name', $name->value)->first();

        return $record ? RoleReflector::reconstruct(
            uuid: RoleId::fromString($record->uuid),
            name: RoleName::from($record->name),
        ) : null;
    }

    public function nextIdentity(): RoleId
    {
        return RoleId::generate();
    }
}
