<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;

class UserSeeder extends Seeder
{
    /** @var array<string, string> Maps user email to their UUID */
    public static array $userIds = [];

    public function __construct(
        private readonly UserRepository $repository,
        private readonly PasswordHasher $hasher,
        private readonly UserEmailUniquenessChecker $emailUniquenessChecker,
    ) {}

    public function run(): void
    {
        $defaultPassword = $this->hasher->hash('password123')->value;

        // Guest-type users (with loyalty tiers)
        $guests = [
            ['Alice Johnson', 'alice@example.com', '5511999990001', '11122233344', LoyaltyTier::BRONZE, []],
            ['Bob Williams', 'bob.vip@example.com', '5511999990002', '55566677788', LoyaltyTier::PLATINUM, ['late_checkout', 'high_floor']],
            ['Carol Davis', 'carol@example.com', '5511999990003', '99988877766', LoyaltyTier::SILVER, ['extra_pillows']],
            ['David Martinez', 'david.m@example.com', '5511999990004', '33344455566', LoyaltyTier::GOLD, []],
            ['Eva Thompson', 'eva.t@example.com', '5511999990005', '77788899900', LoyaltyTier::BRONZE, []],
        ];

        foreach ($guests as [$name, $email, $phone, $document, $tier, $preferences]) {
            $existing = $this->repository->findByDocument($document);

            if ($existing !== null) {
                self::$userIds[$email] = (string) $existing->id();

                continue;
            }

            $id = $this->repository->nextIdentity();
            $user = User::create(
                uuid: $id,
                fullName: $name,
                email: $email,
                phone: $phone,
                document: $document,
                loyaltyTier: $tier,
                preferences: $preferences,
                createdAt: new DateTimeImmutable,
                hashedPassword: $defaultPassword,
                actorType: 'guest',
                emailUniquenessChecker: $this->emailUniquenessChecker,
            );
            $this->repository->save($user);

            self::$userIds[$email] = (string) $id;
        }

        // Owner-type users (no loyalty tier)
        $owners = [
            ['John Smith', 'john@hospitality.com', '5511999990010', '10020030040'],
            ['Maria Santos', 'maria@tourism.com', '5511999990011', '10020030041'],
        ];

        foreach ($owners as [$name, $email, $phone, $document]) {
            $existing = $this->repository->findByDocument($document);

            if ($existing !== null) {
                self::$userIds[$email] = (string) $existing->id();

                continue;
            }

            $id = $this->repository->nextIdentity();
            $user = User::create(
                uuid: $id,
                fullName: $name,
                email: $email,
                phone: $phone,
                document: $document,
                loyaltyTier: null,
                preferences: [],
                createdAt: new DateTimeImmutable,
                hashedPassword: $defaultPassword,
                actorType: 'owner',
                emailUniquenessChecker: $this->emailUniquenessChecker,
            );
            $this->repository->save($user);

            self::$userIds[$email] = (string) $id;
        }
    }
}
