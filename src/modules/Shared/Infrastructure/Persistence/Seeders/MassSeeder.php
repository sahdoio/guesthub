<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MassSeeder extends Seeder
{
    private const ACCOUNTS_COUNT = 20;
    private const HOTELS_COUNT = 50;
    private const ROOMS_TARGET = 500;
    private const GUESTS_COUNT = 500;
    private const RESERVATIONS_COUNT = 800;

    private array $firstNames = [
        'James', 'Mary', 'Robert', 'Patricia', 'John', 'Jennifer', 'Michael', 'Linda',
        'David', 'Elizabeth', 'William', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica',
        'Thomas', 'Sarah', 'Christopher', 'Karen', 'Charles', 'Lisa', 'Daniel', 'Nancy',
        'Matthew', 'Betty', 'Anthony', 'Margaret', 'Mark', 'Sandra', 'Donald', 'Ashley',
        'Steven', 'Dorothy', 'Paul', 'Kimberly', 'Andrew', 'Emily', 'Joshua', 'Donna',
        'Kenneth', 'Michelle', 'Kevin', 'Carol', 'Brian', 'Amanda', 'George', 'Melissa',
        'Timothy', 'Deborah', 'Ronald', 'Stephanie', 'Edward', 'Rebecca', 'Jason', 'Sharon',
        'Jeffrey', 'Laura', 'Ryan', 'Cynthia', 'Jacob', 'Kathleen', 'Gary', 'Amy',
        'Nicholas', 'Angela', 'Eric', 'Shirley', 'Jonathan', 'Anna', 'Stephen', 'Brenda',
        'Larry', 'Pamela', 'Justin', 'Emma', 'Scott', 'Nicole', 'Brandon', 'Helen',
        'Benjamin', 'Samantha', 'Samuel', 'Katherine', 'Raymond', 'Christine', 'Gregory', 'Debra',
        'Frank', 'Rachel', 'Alexander', 'Carolyn', 'Patrick', 'Janet', 'Jack', 'Catherine',
        'Hiroshi', 'Yuki', 'Carlos', 'Maria', 'Pierre', 'Sophie', 'Hans', 'Elena',
    ];

    private array $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
        'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
        'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson',
        'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker',
        'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill',
        'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell',
        'Mitchell', 'Carter', 'Roberts', 'Gomez', 'Phillips', 'Evans', 'Turner', 'Diaz',
        'Parker', 'Cruz', 'Edwards', 'Collins', 'Reyes', 'Stewart', 'Morris', 'Morales',
        'Murphy', 'Cook', 'Rogers', 'Gutierrez', 'Ortiz', 'Morgan', 'Cooper', 'Peterson',
        'Bailey', 'Reed', 'Kelly', 'Howard', 'Ramos', 'Kim', 'Cox', 'Ward',
        'Tanaka', 'Nakamura', 'Dubois', 'Müller', 'Rossi', 'Johansson', 'Svensson', 'Petrov',
    ];

    private array $hotelPrefixes = [
        'The Grand', 'Royal', 'Imperial', 'Majestic', 'The Ritz', 'Belvedere', 'The Plaza',
        'Prestige', 'The Crown', 'Golden', 'Silver', 'Diamond', 'Crystal', 'Emerald',
        'The Luxe', 'Pinnacle', 'The Savoy', 'Heritage', 'Paramount', 'Renaissance',
    ];

    private array $hotelSuffixes = [
        'Hotel', 'Resort', 'Lodge', 'Inn', 'Suites', 'Palace', 'Boutique Hotel',
        'Beach Resort', 'Spa & Resort', 'Hotel & Spa', 'Retreat', 'Residence',
    ];

    private array $hotelThemes = [
        'Sunset Beach', 'Mountain View', 'Harbor View', 'City Center', 'Oceanfront',
        'Lakeside', 'Garden', 'Skyline', 'Riverside', 'Hilltop', 'Coastal', 'Valley',
        'Forest', 'Bay', 'Parkside', 'Seaside', 'Downtown', 'Waterfront', 'Cliffside', 'Island',
    ];

    private array $cities = [
        ['city' => 'New York', 'state' => 'NY', 'streets' => ['Broadway', '5th Avenue', 'Park Avenue', 'Madison Avenue', 'Lexington Avenue']],
        ['city' => 'Miami', 'state' => 'FL', 'streets' => ['Ocean Drive', 'Collins Avenue', 'Biscayne Blvd', 'Lincoln Road', 'Brickell Avenue']],
        ['city' => 'Los Angeles', 'state' => 'CA', 'streets' => ['Sunset Blvd', 'Hollywood Blvd', 'Wilshire Blvd', 'Rodeo Drive', 'Santa Monica Blvd']],
        ['city' => 'Chicago', 'state' => 'IL', 'streets' => ['Michigan Avenue', 'Lake Shore Drive', 'State Street', 'Wacker Drive', 'Clark Street']],
        ['city' => 'San Francisco', 'state' => 'CA', 'streets' => ['Market Street', 'Lombard Street', 'Mission Street', 'Van Ness Avenue', 'Geary Street']],
        ['city' => 'London', 'state' => 'UK', 'streets' => ['Oxford Street', 'Regent Street', 'Bond Street', 'The Strand', 'Piccadilly']],
        ['city' => 'Paris', 'state' => 'FR', 'streets' => ['Champs-Élysées', 'Rue de Rivoli', 'Boulevard Saint-Germain', 'Avenue Montaigne', 'Rue du Faubourg']],
        ['city' => 'Tokyo', 'state' => 'JP', 'streets' => ['Ginza', 'Shibuya Crossing', 'Roppongi Hills', 'Shinjuku', 'Aoyama']],
        ['city' => 'Barcelona', 'state' => 'ES', 'streets' => ['La Rambla', 'Passeig de Gràcia', 'Diagonal Avenue', 'Via Laietana', 'Carrer de Balmes']],
        ['city' => 'Sydney', 'state' => 'AU', 'streets' => ['George Street', 'Pitt Street', 'Oxford Street', 'King Street', 'Macquarie Street']],
        ['city' => 'Dubai', 'state' => 'AE', 'streets' => ['Sheikh Zayed Road', 'Jumeirah Beach Road', 'Al Maktoum Road', 'Palm Jumeirah', 'Dubai Marina Walk']],
        ['city' => 'Rome', 'state' => 'IT', 'streets' => ['Via del Corso', 'Via Veneto', 'Via Condotti', 'Via Nazionale', 'Via della Conciliazione']],
        ['city' => 'Las Vegas', 'state' => 'NV', 'streets' => ['Las Vegas Blvd', 'Fremont Street', 'Paradise Road', 'Flamingo Road', 'Tropicana Avenue']],
        ['city' => 'Boston', 'state' => 'MA', 'streets' => ['Boylston Street', 'Newbury Street', 'Commonwealth Avenue', 'Beacon Street', 'Tremont Street']],
        ['city' => 'Seattle', 'state' => 'WA', 'streets' => ['Pike Street', 'Pine Street', '1st Avenue', 'Aurora Avenue', 'Rainier Avenue']],
    ];

    private array $amenities = [
        'wifi', 'tv', 'minibar', 'safe', 'jacuzzi',
        'balcony', 'ocean_view', 'room_service', 'air_conditioning', 'coffee_maker',
    ];

    private array $cancellationReasons = [
        'Change of travel plans',
        'Found better accommodation',
        'Flight cancelled',
        'Medical emergency',
        'Weather conditions',
        'Work schedule conflict',
        'Family emergency',
        'Double booking',
        'Budget constraints',
        'Destination changed',
    ];

    public function run(): void
    {
        if (DB::table('accounts')->count() > 10) {
            $this->command->info('Mass data already exists. Skipping MassSeeder.');
            return;
        }

        $this->command->info('Starting mass data seeding...');

        $ownerTypeId = DB::table('types')->where('name', 'owner')->value('id');
        $guestTypeId = DB::table('types')->where('name', 'guest')->value('id');

        if (!$ownerTypeId || !$guestTypeId) {
            $this->command->error('Required types (owner, guest) not found. Run TypeSeeder first.');
            return;
        }

        $hashedPassword = Hash::make('password');
        $now = now()->toDateTimeString();

        // 1. Generate accounts
        $this->command->info('Seeding accounts...');
        $accounts = $this->generateAccounts($now);
        $this->batchInsert('accounts', $accounts);
        $accountIds = DB::table('accounts')->orderBy('id')->pluck('id', 'uuid')->toArray();
        $this->command->info(sprintf('  Created %d accounts.', count($accounts)));

        // 2. Generate hotels
        $this->command->info('Seeding hotels...');
        $hotels = $this->generateHotels($accounts, $accountIds, $now);
        $this->batchInsert('hotels', $hotels);
        $hotelIds = DB::table('hotels')->orderBy('id')->pluck('id', 'uuid')->toArray();
        $this->command->info(sprintf('  Created %d hotels.', count($hotels)));

        // 3. Generate rooms
        $this->command->info('Seeding rooms...');
        $rooms = $this->generateRooms($hotels, $accountIds, $hotelIds, $now);
        $this->batchInsert('rooms', $rooms);
        $this->command->info(sprintf('  Created %d rooms.', count($rooms)));

        // 4. Generate guest users
        $this->command->info('Seeding guest users...');
        $guestUsers = $this->generateGuestUsers($now);
        $this->batchInsert('users', $guestUsers);
        $userIds = DB::table('users')->orderBy('id')->pluck('id', 'uuid')->toArray();
        $this->command->info(sprintf('  Created %d guest users.', count($guestUsers)));

        // 5. Generate guest actors (one per guest user, each with their own personal account)
        $this->command->info('Seeding guest actors...');
        $guestActorData = $this->generateGuestActors($guestUsers, $userIds, $hashedPassword, $now);
        $this->batchInsert('accounts', $guestActorData['personalAccounts']);
        // Re-fetch account IDs after adding personal accounts
        $allAccountIds = DB::table('accounts')->orderBy('id')->pluck('id', 'uuid')->toArray();
        // Update guest actors with correct account_id
        foreach ($guestActorData['actors'] as &$actor) {
            $actor['account_id'] = $allAccountIds[$actor['_account_uuid']] ?? $actor['account_id'];
            unset($actor['_account_uuid']);
        }
        unset($actor);
        $this->batchInsert('actors', $guestActorData['actors']);
        $guestActorIds = DB::table('actors')
            ->whereIn('email', array_column($guestActorData['actors'], 'email'))
            ->pluck('id')
            ->toArray();
        $guestActorTypePivots = [];
        foreach ($guestActorIds as $actorId) {
            $guestActorTypePivots[] = [
                'actor_id' => $actorId,
                'type_id' => $guestTypeId,
            ];
        }
        $this->batchInsert('actor_types', $guestActorTypePivots);
        $this->command->info(sprintf('  Created %d guest actors with personal accounts.', count($guestActorData['actors'])));

        // 6. Generate owner actors (one per business account)
        $this->command->info('Seeding owner actors...');
        $ownerActors = $this->generateOwnerActors($accounts, $allAccountIds, $hashedPassword, $now);
        $this->batchInsert('actors', $ownerActors);
        $ownerActorIds = DB::table('actors')
            ->whereIn('email', array_column($ownerActors, 'email'))
            ->pluck('id')
            ->toArray();
        $ownerActorTypePivots = [];
        foreach ($ownerActorIds as $actorId) {
            $ownerActorTypePivots[] = [
                'actor_id' => $actorId,
                'type_id' => $ownerTypeId,
            ];
        }
        $this->batchInsert('actor_types', $ownerActorTypePivots);
        $this->command->info(sprintf('  Created %d owner actors.', count($ownerActors)));

        // 7. Generate reservations
        $this->command->info('Seeding reservations...');
        $reservations = $this->generateReservations($guestUsers, $accounts, $hotels, $rooms, $now);
        $this->batchInsert('reservations', $reservations);
        $this->command->info(sprintf('  Created %d reservations.', count($reservations)));

        $totalRecords = count($accounts) + count($hotels) + count($rooms) + count($guestUsers)
            + count($guestActorData['personalAccounts']) + count($guestActorData['actors'])
            + count($ownerActors) + count($reservations)
            + count($guestActorTypePivots) + count($ownerActorTypePivots);
        $this->command->info(sprintf('Mass seeding complete! Total records inserted: %d', $totalRecords));
    }

    private function generateAccounts(string $now): array
    {
        $accounts = [];
        $businessNames = [
            'Horizon Hospitality Group', 'Stellar Hotels International', 'Azure Resort Collection',
            'Pinnacle Hotel Management', 'Coastal Living Hotels', 'Summit Hospitality Corp',
            'Grandview Hotel Partners', 'Pacific Shores Resorts', 'Metropolitan Hotel Group',
            'Atlas Hospitality Holdings', 'Evergreen Resort Management', 'Crown Plaza Enterprises',
            'Sapphire Bay Hotels', 'Northstar Hospitality', 'Meridian Hotel Collection',
            'Vista Grande Resorts', 'Harborlight Hotels', 'Alpine Retreat Hospitality',
            'Sunstone Hotel Partners', 'Prestige Accommodation Group',
        ];

        for ($i = 0; $i < self::ACCOUNTS_COUNT; $i++) {
            $name = $businessNames[$i];
            $uuid = (string) Str::uuid();
            $accounts[] = [
                'uuid' => $uuid,
                'name' => $name,
                'slug' => Str::slug($name),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $accounts;
    }

    private function generateHotels(array $accounts, array $accountIds, string $now): array
    {
        $hotels = [];
        $usedNames = [];
        $usedSlugs = DB::table('hotels')->pluck('slug')->all();
        $hotelsPerAccount = [];

        // Distribute hotels: 2-3 per account, totaling ~50
        foreach ($accounts as $index => $account) {
            $count = ($index < 10) ? 3 : 2; // First 10 accounts get 3 hotels, rest get 2
            $hotelsPerAccount[$account['uuid']] = $count;
        }

        foreach ($accounts as $account) {
            $count = $hotelsPerAccount[$account['uuid']];
            $accountId = $accountIds[$account['uuid']];

            for ($i = 0; $i < $count; $i++) {
                $name = $this->generateUniqueHotelName($usedNames, $usedSlugs);
                $slug = Str::slug($name);
                $usedNames[] = $name;
                $usedSlugs[] = $slug;
                $cityData = $this->cities[array_rand($this->cities)];
                $street = $cityData['streets'][array_rand($cityData['streets'])];
                $streetNumber = rand(1, 9999);
                $address = sprintf('%d %s, %s, %s', $streetNumber, $street, $cityData['city'], $cityData['state']);
                $uuid = (string) Str::uuid();
                $contactEmail = strtolower(str_replace(' ', '', $slug)) . '@' . Str::slug($account['name']) . '.com';
                $phone = sprintf('+1-%03d-%03d-%04d', rand(200, 999), rand(200, 999), rand(1000, 9999));

                $hotels[] = [
                    'uuid' => $uuid,
                    'account_id' => $accountId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => sprintf('Welcome to %s, a premier destination offering world-class hospitality in the heart of %s.', $name, $cityData['city']),
                    'address' => $address,
                    'contact_email' => $contactEmail,
                    'contact_phone' => $phone,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                    '_account_uuid' => $account['uuid'],
                ];
            }
        }

        // Remove internal tracking field before insert
        return array_map(function ($hotel) {
            unset($hotel['_account_uuid']);
            return $hotel;
        }, $hotels);
    }

    private function generateUniqueHotelName(array $usedNames, array $usedSlugs): string
    {
        $maxAttempts = 100;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $style = rand(1, 3);
            $name = match ($style) {
                1 => $this->hotelPrefixes[array_rand($this->hotelPrefixes)] . ' ' . $this->hotelSuffixes[array_rand($this->hotelSuffixes)],
                2 => $this->hotelThemes[array_rand($this->hotelThemes)] . ' ' . $this->hotelSuffixes[array_rand($this->hotelSuffixes)],
                3 => $this->hotelPrefixes[array_rand($this->hotelPrefixes)] . ' ' . $this->hotelThemes[array_rand($this->hotelThemes)] . ' ' . $this->hotelSuffixes[array_rand($this->hotelSuffixes)],
            };

            $slug = Str::slug($name);
            if (!in_array($name, $usedNames, true) && !in_array($slug, $usedSlugs, true)) {
                return $name;
            }
        }

        // Fallback with random suffix
        return $this->hotelPrefixes[array_rand($this->hotelPrefixes)] . ' Hotel #' . rand(100, 999);
    }

    private function generateRooms(array $hotels, array $accountIds, array $hotelIds, string $now): array
    {
        $rooms = [];
        $roomsPerHotel = [];
        $totalHotels = count($hotels);

        // Distribute ~500 rooms across hotels (8-15 per hotel)
        $remaining = self::ROOMS_TARGET;
        for ($i = 0; $i < $totalHotels; $i++) {
            if ($i === $totalHotels - 1) {
                $count = max(8, $remaining);
            } else {
                $count = rand(8, 15);
                $remaining -= $count;
                if ($remaining < 8 * ($totalHotels - $i - 1)) {
                    $count = max(8, $count - (8 * ($totalHotels - $i - 1) - $remaining));
                    $remaining = self::ROOMS_TARGET - array_sum($roomsPerHotel) - $count;
                }
            }
            $roomsPerHotel[] = $count;
        }

        foreach ($hotels as $hIndex => $hotel) {
            $hotelUuid = $hotel['uuid'];
            $hotelId = $hotelIds[$hotelUuid];
            $accountId = $hotel['account_id'];
            $roomCount = $roomsPerHotel[$hIndex] ?? 10;

            for ($r = 0; $r < $roomCount; $r++) {
                $typeRoll = rand(1, 100);
                if ($typeRoll <= 40) {
                    $type = 'SINGLE';
                    $price = rand(80, 200) + (rand(0, 99) / 100);
                    $capacity = rand(1, 2);
                } elseif ($typeRoll <= 80) {
                    $type = 'DOUBLE';
                    $price = rand(120, 350) + (rand(0, 99) / 100);
                    $capacity = rand(2, 4);
                } else {
                    $type = 'SUITE';
                    $price = rand(250, 800) + (rand(0, 99) / 100);
                    $capacity = rand(2, 6);
                }

                $floor = rand(1, 10);
                $roomNumber = ($floor * 100) + $r + 1;
                $amenityCount = rand(3, 8);
                $shuffled = $this->amenities;
                shuffle($shuffled);
                $selectedAmenities = array_slice($shuffled, 0, $amenityCount);

                $statusRoll = rand(1, 100);
                $status = match (true) {
                    $statusRoll <= 70 => 'AVAILABLE',
                    $statusRoll <= 85 => 'OCCUPIED',
                    $statusRoll <= 95 => 'MAINTENANCE',
                    default => 'OUT_OF_ORDER',
                };

                $rooms[] = [
                    'uuid' => (string) Str::uuid(),
                    'account_id' => $accountId,
                    'hotel_id' => $hotelId,
                    'number' => (string) $roomNumber,
                    'type' => $type,
                    'floor' => $floor,
                    'capacity' => $capacity,
                    'price_per_night' => round($price, 2),
                    'status' => $status,
                    'amenities' => json_encode($selectedAmenities),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $rooms;
    }

    private function generateGuestUsers(string $now): array
    {
        $users = [];
        $usedEmails = [];
        $loyaltyDistribution = array_merge(
            array_fill(0, 40, 'bronze'),
            array_fill(0, 30, 'silver'),
            array_fill(0, 20, 'gold'),
            array_fill(0, 10, 'platinum'),
        );

        for ($i = 0; $i < self::GUESTS_COUNT; $i++) {
            $firstName = $this->firstNames[array_rand($this->firstNames)];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = $firstName . ' ' . $lastName;

            $baseEmail = strtolower($firstName . '.' . $lastName);
            $email = $baseEmail . '@example.com';
            $counter = 1;
            while (in_array($email, $usedEmails, true)) {
                $email = $baseEmail . $counter . '@example.com';
                $counter++;
            }
            $usedEmails[] = $email;

            $phone = sprintf('+1-%03d-%03d-%04d', rand(200, 999), rand(200, 999), rand(1000, 9999));
            $document = sprintf('%03d-%02d-%04d', rand(100, 999), rand(10, 99), rand(1000, 9999));
            $loyaltyTier = $loyaltyDistribution[array_rand($loyaltyDistribution)];

            $preferences = json_encode([
                'room_type' => ['SINGLE', 'DOUBLE', 'SUITE'][array_rand(['SINGLE', 'DOUBLE', 'SUITE'])],
                'floor' => rand(1, 10) > 5 ? 'high' : 'low',
                'smoking' => rand(1, 100) <= 15,
                'newsletter' => rand(1, 100) <= 60,
            ]);

            $users[] = [
                'uuid' => (string) Str::uuid(),
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'document' => $document,
                'loyalty_tier' => $loyaltyTier,
                'preferences' => $preferences,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $users;
    }

    private function generateGuestActors(array $guestUsers, array $userIds, string $hashedPassword, string $now): array
    {
        $actors = [];
        $personalAccounts = [];

        foreach ($guestUsers as $user) {
            $personalAccountUuid = (string) Str::uuid();
            $personalAccountName = $user['full_name'] . ' Personal';
            $personalAccounts[] = [
                'uuid' => $personalAccountUuid,
                'name' => $personalAccountName,
                'slug' => Str::slug($personalAccountName) . '-' . substr($personalAccountUuid, 0, 8),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $userId = $userIds[$user['uuid']] ?? null;

            $actors[] = [
                'uuid' => (string) Str::uuid(),
                'account_id' => 0, // Will be updated after personal accounts are inserted
                '_account_uuid' => $personalAccountUuid,
                'name' => $user['full_name'],
                'email' => $user['email'],
                'password' => $hashedPassword,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return [
            'actors' => $actors,
            'personalAccounts' => $personalAccounts,
        ];
    }

    private function generateOwnerActors(array $accounts, array $accountIds, string $hashedPassword, string $now): array
    {
        $actors = [];

        foreach ($accounts as $account) {
            $firstName = $this->firstNames[array_rand($this->firstNames)];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = $firstName . ' ' . $lastName;
            $email = strtolower($firstName . '.' . $lastName) . '@' . Str::slug($account['name']) . '.com';
            $accountId = $accountIds[$account['uuid']];

            $actors[] = [
                'uuid' => (string) Str::uuid(),
                'account_id' => $accountId,
                'name' => $fullName,
                'email' => $email,
                'password' => $hashedPassword,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $actors;
    }

    private function generateReservations(array $guestUsers, array $accounts, array $hotels, array $rooms, string $now): array
    {
        $reservations = [];
        $statusDistribution = array_merge(
            array_fill(0, 30, 'pending'),
            array_fill(0, 25, 'confirmed'),
            array_fill(0, 20, 'checked_in'),
            array_fill(0, 15, 'checked_out'),
            array_fill(0, 10, 'cancelled'),
        );

        $roomTypes = ['SINGLE', 'DOUBLE', 'SUITE'];

        // Build lookup maps from DB
        $dbHotels = DB::table('hotels')->get(['id', 'uuid', 'account_id'])->keyBy('uuid');
        $dbAccounts = DB::table('accounts')->get(['id', 'uuid'])->keyBy('id');

        // Build hotel-to-rooms mapping for room number assignment
        $hotelRooms = [];
        foreach ($rooms as $room) {
            $hotelRooms[$room['hotel_id']][] = $room;
        }

        for ($i = 0; $i < self::RESERVATIONS_COUNT; $i++) {
            $guestUser = $guestUsers[array_rand($guestUsers)];
            $hotel = $hotels[array_rand($hotels)];
            $hotelDb = $dbHotels[$hotel['uuid']] ?? null;

            if (!$hotelDb) {
                continue;
            }

            $accountDb = $dbAccounts[$hotelDb->account_id] ?? null;
            $accountId = $hotelDb->account_id;
            $accountUuid = $accountDb->uuid ?? null;
            $hotelDbId = $hotelDb->id;

            $status = $statusDistribution[array_rand($statusDistribution)];
            $roomType = $roomTypes[array_rand($roomTypes)];

            // Generate dates spread across past 3 months and next 3 months
            $daysOffset = rand(-90, 90);
            $checkIn = now()->addDays($daysOffset);
            $stayLength = rand(1, 14);
            $checkOut = (clone $checkIn)->addDays($stayLength);

            $checkInStr = $checkIn->toDateTimeString();
            $checkOutStr = $checkOut->toDateTimeString();

            // Assign room number from matching hotel rooms if possible
            $assignedRoomNumber = null;
            if (in_array($status, ['checked_in', 'checked_out', 'confirmed'], true)) {
                $availableRooms = $hotelRooms[$hotelDbId] ?? [];
                if (!empty($availableRooms)) {
                    $selectedRoom = $availableRooms[array_rand($availableRooms)];
                    $assignedRoomNumber = $selectedRoom['number'];
                }
            }

            $confirmedAt = null;
            $checkedInAt = null;
            $checkedOutAt = null;
            $cancelledAt = null;
            $cancellationReason = null;

            if (in_array($status, ['confirmed', 'checked_in', 'checked_out'], true)) {
                $confirmedAt = (clone $checkIn)->subDays(rand(1, 30))->toDateTimeString();
            }
            if (in_array($status, ['checked_in', 'checked_out'], true)) {
                $checkedInAt = $checkInStr;
            }
            if ($status === 'checked_out') {
                $checkedOutAt = $checkOutStr;
            }
            if ($status === 'cancelled') {
                $cancelledAt = (clone $checkIn)->subDays(rand(1, 15))->toDateTimeString();
                $cancellationReason = $this->cancellationReasons[array_rand($this->cancellationReasons)];
            }

            $reservations[] = [
                'uuid' => (string) Str::uuid(),
                'guest_id' => $guestUser['uuid'],
                'account_id' => $accountId,
                'account_uuid' => $accountUuid,
                'hotel_id' => $hotelDbId,
                'hotel_uuid' => $hotel['uuid'],
                'check_in' => $checkInStr,
                'check_out' => $checkOutStr,
                'room_type' => $roomType,
                'status' => $status,
                'assigned_room_number' => $assignedRoomNumber,
                'created_at' => (clone $checkIn)->subDays(rand(15, 60))->toDateTimeString(),
                'confirmed_at' => $confirmedAt,
                'checked_in_at' => $checkedInAt,
                'checked_out_at' => $checkedOutAt,
                'cancelled_at' => $cancelledAt,
                'cancellation_reason' => $cancellationReason,
            ];
        }

        return $reservations;
    }

    private function batchInsert(string $table, array $records, int $chunkSize = 100): void
    {
        $chunks = array_chunk($records, $chunkSize);
        foreach ($chunks as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
