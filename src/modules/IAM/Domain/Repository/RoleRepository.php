<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\RoleName;

interface RoleRepository
{
    public function save(Role $role): void;

    public function findByUuid(RoleId $uuid): ?Role;

    public function findByName(RoleName $name): ?Role;

    public function nextIdentity(): RoleId;
}
