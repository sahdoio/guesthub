<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Domain\Repository\RoleRepository;
use Modules\IAM\Domain\Role;
use Modules\IAM\Domain\ValueObject\RoleName;

class RoleSeeder extends Seeder
{
    public function __construct(
        private readonly RoleRepository $repository,
    ) {}

    public function run(): void
    {
        foreach (RoleName::cases() as $roleName) {
            $existing = $this->repository->findByName($roleName);

            if ($existing !== null) {
                continue;
            }

            $role = Role::create(
                uuid: $this->repository->nextIdentity(),
                name: $roleName,
            );

            $this->repository->save($role);
        }
    }
}
