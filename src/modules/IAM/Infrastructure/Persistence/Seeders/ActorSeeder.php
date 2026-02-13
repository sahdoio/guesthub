<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\Guest\Infrastructure\Persistence\Seeders\GuestSeeder;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\ActorType;

class ActorSeeder extends Seeder
{
    public function __construct(
        private readonly ActorRepository $repository,
        private readonly PasswordHasher $hasher,
    ) {}

    public function run(): void
    {
        $guestIds = GuestSeeder::$guestIds;

        $actors = [
            ['Alice Johnson', 'alice@example.com'],
            ['Bob Williams', 'bob.vip@example.com'],
            ['Carol Davis', 'carol@example.com'],
            ['David Martinez', 'david.m@example.com'],
            ['Eva Thompson', 'eva.t@example.com'],
        ];

        foreach ($actors as [$name, $email]) {
            $guestProfileUuid = $guestIds[$email];

            $actor = Actor::register(
                uuid: $this->repository->nextIdentity(),
                type: ActorType::GUEST,
                name: $name,
                email: $email,
                password: $this->hasher->hash('password123'),
                profileType: ActorType::GUEST->value,
                profileId: $guestProfileUuid,
                createdAt: new DateTimeImmutable(),
            );

            $this->repository->save($actor);
        }
    }
}
