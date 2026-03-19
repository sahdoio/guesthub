<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Hotel;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

class HotelSeeder extends Seeder
{
    /** @var array<string, string> Maps hotel slug to UUID */
    public static array $hotelSlugs = [];

    public static ?string $defaultHotelUuid = null;

    private const HOTELS = [
        // John owns two hotels
        [
            'account_slug' => 'johns-hospitality',
            'name' => 'Grand Plaza Hotel',
            'slug' => 'grand-plaza-hotel',
            'description' => 'A modern downtown hotel with world-class amenities and exceptional service.',
            'address' => '123 Main Street, Downtown',
            'contact_email' => 'info@grandplaza.com',
            'contact_phone' => '5511999990000',
        ],
        [
            'account_slug' => 'johns-hospitality',
            'name' => 'Seaside Resort & Spa',
            'slug' => 'seaside-resort',
            'description' => 'Luxury beachfront resort with spa, pool, and ocean views.',
            'address' => '456 Ocean Drive, Beachside',
            'contact_email' => 'reservations@seaside-resort.com',
            'contact_phone' => '5511999991000',
        ],
        // Maria owns one hotel
        [
            'account_slug' => 'marias-tourism',
            'name' => 'Mountain Lodge Inn',
            'slug' => 'mountain-lodge',
            'description' => 'Cozy mountain retreat perfect for nature lovers and adventurers.',
            'address' => '789 Alpine Road, Highlands',
            'contact_email' => 'stay@mountain-lodge.com',
            'contact_phone' => '5511999992000',
        ],
    ];

    public function __construct(
        private readonly HotelRepository $hotelRepository,
        private readonly AccountRepository $accountRepository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function run(): void
    {
        foreach (self::HOTELS as $data) {
            $accountUuid = AccountSeeder::$accountSlugs[$data['account_slug']] ?? null;
            if ($accountUuid === null) {
                continue;
            }

            $existing = $this->hotelRepository->findByName($data['name']);
            if ($existing !== null) {
                self::$hotelSlugs[$data['slug']] = (string) $existing->uuid;
                if (self::$defaultHotelUuid === null) {
                    self::$defaultHotelUuid = (string) $existing->uuid;
                }
                continue;
            }

            $accountId = AccountId::fromString($accountUuid);
            $numericAccountId = $this->accountRepository->resolveNumericId($accountId);
            $this->tenantContext->set($numericAccountId);

            $hotelId = $this->hotelRepository->nextIdentity();
            $hotel = Hotel::create(
                uuid: $hotelId,
                accountId: $accountId,
                name: $data['name'],
                slug: $data['slug'],
                createdAt: new DateTimeImmutable,
                description: $data['description'],
                address: $data['address'],
                contactEmail: $data['contact_email'],
                contactPhone: $data['contact_phone'],
            );

            $this->hotelRepository->save($hotel);

            self::$hotelSlugs[$data['slug']] = $hotelId->value;
            if (self::$defaultHotelUuid === null) {
                self::$defaultHotelUuid = $hotelId->value;
            }
        }
    }
}
