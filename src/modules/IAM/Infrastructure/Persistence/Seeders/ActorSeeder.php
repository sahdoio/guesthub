<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\IAM\Domain\UserId;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Infrastructure\Persistence\Seeders\UserSeeder;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Domain\Service\EmailUniquenessChecker;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\TypeName;

class ActorSeeder extends Seeder
{
    public function __construct(
        private readonly ActorRepository $repository,
        private readonly AccountRepository $accountRepository,
        private readonly TypeRepository $typeRepository,
        private readonly UserRepository $userRepository,
        private readonly PasswordHasher $hasher,
        private readonly EmailUniquenessChecker $emailChecker,
    ) {}

    public function run(): void
    {
        $this->seedSuperAdmins();
        $this->seedOwners();
        $this->seedGuests();
    }

    private function seedSuperAdmins(): void
    {
        $superadminType = $this->typeRepository->findByName(TypeName::SUPERADMIN);

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
                typeIds: [$superadminType->uuid],
                name: $name,
                email: $email,
                password: $this->hasher->hash('password'),
                userId: null,
                createdAt: new DateTimeImmutable,
                emailUniquenessChecker: $this->emailChecker,
            );

            $this->repository->save($actor);
        }
    }

    private function seedOwners(): void
    {
        $ownerType = $this->typeRepository->findByName(TypeName::OWNER);
        $userIds = UserSeeder::$userIds;

        $owners = [
            ['John Smith', 'john@hospitality.com', AccountSeeder::$accountSlugs['johns-hospitality'] ?? null],
            ['Maria Santos', 'maria@tourism.com', AccountSeeder::$accountSlugs['marias-tourism'] ?? null],
        ];

        foreach ($owners as [$name, $email, $accountUuid]) {
            if ($accountUuid === null || $this->repository->findByEmail($email) !== null) {
                continue;
            }

            $userUuid = $userIds[$email] ?? null;
            $userId = $userUuid ? $this->userRepository->resolveNumericId(UserId::fromString($userUuid)) : null;

            $actor = Actor::register(
                uuid: $this->repository->nextIdentity(),
                accountId: AccountId::fromString($accountUuid),
                typeIds: [$ownerType->uuid],
                name: $name,
                email: $email,
                password: $this->hasher->hash('password'),
                userId: $userId,
                createdAt: new DateTimeImmutable,
                emailUniquenessChecker: $this->emailChecker,
            );

            $this->repository->save($actor);
        }
    }

    private function seedGuests(): void
    {
        $guestType = $this->typeRepository->findByName(TypeName::GUEST);
        $userIds = UserSeeder::$userIds;

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

            // Each guest gets their own personal account
            $accountId = $this->accountRepository->nextIdentity();
            $account = Account::create(
                uuid: $accountId,
                name: $name . "'s Account",
                slug: Str::slug($name) . '-' . Str::random(6),
                createdAt: new DateTimeImmutable,
            );
            $this->accountRepository->save($account);

            $userUuid = $userIds[$email];
            $userId = $this->userRepository->resolveNumericId(UserId::fromString($userUuid));

            $actor = Actor::register(
                uuid: $this->repository->nextIdentity(),
                accountId: $accountId,
                typeIds: [$guestType->uuid],
                name: $name,
                email: $email,
                password: $this->hasher->hash('password'),
                userId: $userId,
                createdAt: new DateTimeImmutable,
                emailUniquenessChecker: $this->emailChecker,
            );

            $this->repository->save($actor);
        }
    }
}
