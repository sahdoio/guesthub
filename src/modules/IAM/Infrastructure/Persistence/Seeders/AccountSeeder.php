<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\IAM\Domain\Account;
use Modules\IAM\Domain\Repository\AccountRepository;

class AccountSeeder extends Seeder
{
    public static ?string $defaultAccountUuid = null;

    /** @var array<string, string> Maps account slug to UUID */
    public static array $accountSlugs = [];

    private const ACCOUNTS = [
        ['name' => "John's Hospitality", 'slug' => 'johns-hospitality'],
        ['name' => "Maria's Tourism Group", 'slug' => 'marias-tourism'],
    ];

    public function __construct(
        private readonly AccountRepository $repository,
    ) {}

    public function run(): void
    {
        foreach (self::ACCOUNTS as $data) {
            $existing = $this->repository->findByName($data['name']);

            if ($existing !== null) {
                if (self::$defaultAccountUuid === null) {
                    self::$defaultAccountUuid = (string) $existing->uuid;
                }

                self::$accountSlugs[$data['slug']] = (string) $existing->uuid;

                continue;
            }

            $id = $this->repository->nextIdentity();
            $account = Account::create(
                uuid: $id,
                name: $data['name'],
                slug: $data['slug'],
                createdAt: new DateTimeImmutable,
            );

            $this->repository->save($account);

            if (self::$defaultAccountUuid === null) {
                self::$defaultAccountUuid = $id->value;
            }

            self::$accountSlugs[$data['slug']] = $id->value;
        }
    }
}
