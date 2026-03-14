<?php

declare(strict_types=1);

namespace Modules\IAM\Domain\Repository;

use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\RoleId;
use Modules\IAM\Domain\ValueObject\RoleName;

interface ActorRepository
{
    public function save(Actor $actor): void;

    public function findByUuid(ActorId $uuid): ?Actor;

    public function findByEmail(string $email): ?Actor;

    public function nextIdentity(): ActorId;

    public function saveRole(Role $role): void;

    public function findRoleByName(RoleName $name): ?Role;

    public function nextRoleIdentity(): RoleId;
}
