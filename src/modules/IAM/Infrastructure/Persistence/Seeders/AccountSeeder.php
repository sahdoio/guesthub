<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;

class AccountSeeder extends Seeder
{
    public static ?string $defaultAccountUuid = null;

    private const ACCOUNTS = [
        'GuestHub Hotel',
        'Seaside Resort & Spa',
        'Mountain Lodge Inn',
    ];

    public function __construct(
        private readonly AccountRepository $repository,
    ) {}

    public function run(): void
    {
        foreach (self::ACCOUNTS as $name) {
            $existing = AccountModel::where('name', $name)->first();

            if ($existing !== null) {
                if (self::$defaultAccountUuid === null) {
                    self::$defaultAccountUuid = $existing->uuid;
                }

                continue;
            }

            $id = $this->repository->nextIdentity();
            $account = Account::create(
                uuid: $id,
                name: $name,
                createdAt: new DateTimeImmutable,
            );

            $this->repository->save($account);

            if (self::$defaultAccountUuid === null) {
                self::$defaultAccountUuid = $id->value;
            }
        }
    }
}
