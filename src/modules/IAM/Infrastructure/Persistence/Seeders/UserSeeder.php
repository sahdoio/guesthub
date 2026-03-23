<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;

class UserSeeder extends Seeder
{
    /** @var array<string, string> Maps user email to their UUID */
    public static array $userIds = [];

    public function __construct(
        private readonly UserRepository $repository,
    ) {}

    public function run(): void
    {
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
            $user = User::create($id, $name, $email, $phone, $document, $tier, $preferences, new DateTimeImmutable);
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
            $user = User::create($id, $name, $email, $phone, $document, null, [], new DateTimeImmutable);
            $this->repository->save($user);

            self::$userIds[$email] = (string) $id;
        }
    }
}
