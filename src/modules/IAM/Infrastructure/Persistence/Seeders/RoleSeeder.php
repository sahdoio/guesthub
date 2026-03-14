<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\ValueObject\RoleName;

class RoleSeeder extends Seeder
{
    public function __construct(
        private readonly ActorRepository $repository,
    ) {}

    public function run(): void
    {
        foreach (RoleName::cases() as $roleName) {
            $existing = $this->repository->findRoleByName($roleName);

            if ($existing !== null) {
                continue;
            }

            $role = Role::create(
                uuid: $this->repository->nextRoleIdentity(),
                name: $roleName,
            );

            $this->repository->saveRole($role);
        }
    }
}
