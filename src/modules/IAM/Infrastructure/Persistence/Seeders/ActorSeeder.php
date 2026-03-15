<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Infrastructure\Persistence\Seeders\GuestSeeder;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\RoleRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\RoleName;

class ActorSeeder extends Seeder
{
    public function __construct(
        private readonly ActorRepository $repository,
        private readonly RoleRepository $roleRepository,
        private readonly GuestRepository $guestRepository,
        private readonly PasswordHasher $hasher,
    ) {}

    public function run(): void
    {
        $this->seedSuperAdmins();
        $this->seedAdmins();
        $this->seedGuests();
    }

    private function seedSuperAdmins(): void
    {
        $superadminRole = $this->roleRepository->findByName(RoleName::SUPERADMIN);

        $superadmins = [
            ['Super Admin', 'superadmin@guesthub.com'],
        ];

        foreach ($superadmins as [$name, $email]) {
            if ($this->repository->findByEmail($email) !== null) {
                continue;
            }

            $actor = Actor::register(
                uuid: $this->repository->nextIdentity(),
                accountId: null,
                roleIds: [$superadminRole->uuid],
                name: $name,
                email: $email,
                password: $this->hasher->hash('password'),
                subjectType: null,
                subjectId: null,
                createdAt: new DateTimeImmutable,
            );

            $this->repository->save($actor);
        }
    }

    private function seedAdmins(): void
    {
        $adminRole = $this->roleRepository->findByName(RoleName::ADMIN);
        $accountId = AccountId::fromString(AccountSeeder::$defaultAccountUuid);

        $admins = [
            ['Admin', 'admin@guesthub.com'],
            ['Front Desk', 'frontdesk@guesthub.com'],
        ];

        foreach ($admins as [$name, $email]) {
            if ($this->repository->findByEmail($email) !== null) {
                continue;
            }

            $actor = Actor::register(
                uuid: $this->repository->nextIdentity(),
                accountId: $accountId,
                roleIds: [$adminRole->uuid],
                name: $name,
                email: $email,
                password: $this->hasher->hash('password'),
                subjectType: null,
                subjectId: null,
                createdAt: new DateTimeImmutable,
            );

            $this->repository->save($actor);
        }
    }

    private function seedGuests(): void
    {
        $guestRole = $this->roleRepository->findByName(RoleName::GUEST);
        $accountId = AccountId::fromString(AccountSeeder::$defaultAccountUuid);
        $guestIds = GuestSeeder::$guestIds;

        $guests = [
            ['Alice Johnson', 'alice@example.com'],
            ['Bob Williams', 'bob.vip@example.com'],
            ['Carol Davis', 'carol@example.com'],
            ['David Martinez', 'david.m@example.com'],
            ['Eva Thompson', 'eva.t@example.com'],
        ];

        foreach ($guests as [$name, $email]) {
            if ($this->repository->findByEmail($email) !== null) {
                continue;
            }

            $guestUuid = $guestIds[$email];
            $guestId = $this->guestRepository->resolveNumericId(GuestId::fromString($guestUuid));

            $actor = Actor::register(
                uuid: $this->repository->nextIdentity(),
                accountId: $accountId,
                roleIds: [$guestRole->uuid],
                name: $name,
                email: $email,
                password: $this->hasher->hash('password'),
                subjectType: 'guest',
                subjectId: $guestId,
                createdAt: new DateTimeImmutable,
            );

            $this->repository->save($actor);
        }
    }
}
