<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Infrastructure\Persistence\Seeders\AccountSeeder;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

class GuestSeeder extends Seeder
{
    /** @var array<string, string> Maps guest email to their UUID */
    public static array $guestIds = [];

    public function __construct(
        private readonly GuestRepository $repository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function run(): void
    {
        $accountId = (int) AccountModel::where('uuid', AccountSeeder::$defaultAccountUuid)->value('id');
        $this->tenantContext->set($accountId);

        $guests = [
            ['Alice Johnson', 'alice@example.com', '+5511999990001', '11122233344', LoyaltyTier::BRONZE, []],
            ['Bob Williams', 'bob.vip@example.com', '+5511999990002', '55566677788', LoyaltyTier::PLATINUM, ['late_checkout', 'high_floor']],
            ['Carol Davis', 'carol@example.com', '+5511999990003', '99988877766', LoyaltyTier::SILVER, ['extra_pillows']],
            ['David Martinez', 'david.m@example.com', '+5511999990004', '33344455566', LoyaltyTier::GOLD, []],
            ['Eva Thompson', 'eva.t@example.com', '+5511999990005', '77788899900', LoyaltyTier::BRONZE, []],
        ];

        foreach ($guests as [$name, $email, $phone, $document, $tier, $preferences]) {
            $existing = $this->repository->findByDocument($document);

            if ($existing !== null) {
                self::$guestIds[$email] = (string) $existing->id();

                continue;
            }

            $id = $this->repository->nextIdentity();
            $guest = Guest::create($id, $name, $email, $phone, $document, $tier, $preferences, new DateTimeImmutable);
            $this->repository->save($guest);

            self::$guestIds[$email] = (string) $id;
        }
    }
}
