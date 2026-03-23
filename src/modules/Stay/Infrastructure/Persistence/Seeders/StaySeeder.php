<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Infrastructure\Persistence\Seeders\AccountSeeder;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Domain\ValueObject\StayCategory;
use Modules\Stay\Domain\ValueObject\StayType;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

class StaySeeder extends Seeder
{
    /** @var array<string, string> Maps stay slug to UUID */
    public static array $staySlugs = [];

    public static ?string $defaultStayUuid = null;

    private const STAYS = [
        // John owns hotel rooms and an apartment
        [
            'account_slug' => 'johns-hospitality',
            'name' => 'Grand Plaza - Deluxe Room',
            'slug' => 'grand-plaza-deluxe',
            'type' => 'room',
            'category' => 'hotel_room',
            'price_per_night' => 250.00,
            'capacity' => 2,
            'description' => 'A modern downtown hotel room with world-class amenities and exceptional service.',
            'address' => '123 Main Street, Downtown',
            'contact_email' => 'info@grandplaza.com',
            'contact_phone' => '5511999990000',
            'amenities' => ['wifi', 'tv', 'minibar', 'safe'],
        ],
        [
            'account_slug' => 'johns-hospitality',
            'name' => 'Grand Plaza - Suite',
            'slug' => 'grand-plaza-suite',
            'type' => 'room',
            'category' => 'hotel_room',
            'price_per_night' => 500.00,
            'capacity' => 4,
            'description' => 'Luxury suite with panoramic city views and premium amenities.',
            'address' => '123 Main Street, Downtown',
            'contact_email' => 'info@grandplaza.com',
            'contact_phone' => '5511999990000',
            'amenities' => ['wifi', 'tv', 'minibar', 'safe', 'jacuzzi', 'balcony'],
        ],
        [
            'account_slug' => 'johns-hospitality',
            'name' => 'Seaside Apartment',
            'slug' => 'seaside-apartment',
            'type' => 'entire_space',
            'category' => 'apartment',
            'price_per_night' => 350.00,
            'capacity' => 4,
            'description' => 'Beachfront apartment with full kitchen and ocean views.',
            'address' => '456 Ocean Drive, Beachside',
            'contact_email' => 'reservations@seaside-resort.com',
            'contact_phone' => '5511999991000',
            'amenities' => ['wifi', 'tv', 'kitchen', 'washer', 'balcony'],
        ],
        // Maria owns a lodge and a house
        [
            'account_slug' => 'marias-tourism',
            'name' => 'Mountain Lodge - Standard Room',
            'slug' => 'mountain-lodge-standard',
            'type' => 'room',
            'category' => 'hotel_room',
            'price_per_night' => 180.00,
            'capacity' => 2,
            'description' => 'Cozy mountain room perfect for nature lovers.',
            'address' => '789 Alpine Road, Highlands',
            'contact_email' => 'stay@mountain-lodge.com',
            'contact_phone' => '5511999992000',
            'amenities' => ['wifi', 'fireplace', 'tv'],
        ],
        [
            'account_slug' => 'marias-tourism',
            'name' => 'Alpine Chalet',
            'slug' => 'alpine-chalet',
            'type' => 'entire_space',
            'category' => 'house',
            'price_per_night' => 450.00,
            'capacity' => 6,
            'description' => 'Spacious mountain house with stunning valley views and private hot tub.',
            'address' => '101 Summit Lane, Highlands',
            'contact_email' => 'stay@mountain-lodge.com',
            'contact_phone' => '5511999992000',
            'amenities' => ['wifi', 'fireplace', 'kitchen', 'hot_tub', 'parking'],
        ],
        [
            'account_slug' => 'marias-tourism',
            'name' => 'Downtown City Apartment',
            'slug' => 'downtown-city-apartment',
            'type' => 'entire_space',
            'category' => 'apartment',
            'price_per_night' => 200.00,
            'capacity' => 3,
            'description' => 'Modern apartment in the heart of the city, close to all attractions.',
            'address' => '55 Central Ave, City Center',
            'contact_email' => 'stay@mountain-lodge.com',
            'contact_phone' => '5511999992000',
            'amenities' => ['wifi', 'tv', 'kitchen', 'washer', 'gym'],
        ],
    ];

    public function __construct(
        private readonly StayRepository $stayRepository,
        private readonly AccountRepository $accountRepository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function run(): void
    {
        foreach (self::STAYS as $data) {
            $accountUuid = AccountSeeder::$accountSlugs[$data['account_slug']] ?? null;
            if ($accountUuid === null) {
                continue;
            }

            $existing = $this->stayRepository->findByName($data['name']);
            if ($existing !== null) {
                self::$staySlugs[$data['slug']] = (string) $existing->uuid;
                if (self::$defaultStayUuid === null) {
                    self::$defaultStayUuid = (string) $existing->uuid;
                }
                continue;
            }

            $accountId = AccountId::fromString($accountUuid);
            $numericAccountId = $this->accountRepository->resolveNumericId($accountId);
            $this->tenantContext->set($numericAccountId);

            $stayId = $this->stayRepository->nextIdentity();
            $stay = Stay::create(
                uuid: $stayId,
                accountId: $accountId,
                name: $data['name'],
                slug: $data['slug'],
                type: StayType::from($data['type']),
                category: StayCategory::from($data['category']),
                pricePerNight: $data['price_per_night'],
                capacity: $data['capacity'],
                createdAt: new DateTimeImmutable,
                description: $data['description'],
                address: $data['address'],
                contactEmail: $data['contact_email'],
                contactPhone: $data['contact_phone'],
                amenities: $data['amenities'],
            );

            $this->stayRepository->save($stay);

            self::$staySlugs[$data['slug']] = $stayId->value;
            if (self::$defaultStayUuid === null) {
                self::$defaultStayUuid = $stayId->value;
            }
        }
    }
}
