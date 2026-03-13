<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\ValueObject\RoomType;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndAccount;

    private ActorModel $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndAccount();
        $this->actor = $this->createAdminActor();
    }

    private function createGuest(array $overrides = []): string
    {
        $repository = $this->app->make(GuestRepository::class);

        $guest = Guest::create(
            uuid: $repository->nextIdentity(),
            fullName: $overrides['full_name'] ?? 'Jane Doe',
            email: $overrides['email'] ?? 'jane'.uniqid().'@hotel.com',
            phone: $overrides['phone'] ?? '+5511999999999',
            document: $overrides['document'] ?? 'DOC'.uniqid(),
            loyaltyTier: LoyaltyTier::from($overrides['loyalty_tier'] ?? 'bronze'),
            preferences: [],
            createdAt: new DateTimeImmutable,
        );

        $repository->save($guest);

        return (string) $guest->uuid;
    }

    private function createReservation(string $guestId, array $overrides = []): Reservation
    {
        $repository = $this->app->make(ReservationRepository::class);

        $reservation = Reservation::create(
            uuid: $repository->nextIdentity(),
            guestId: $guestId,
            period: new ReservationPeriod(
                new DateTimeImmutable($overrides['check_in'] ?? '+1 day'),
                new DateTimeImmutable($overrides['check_out'] ?? '+3 days'),
            ),
            roomType: $overrides['room_type'] ?? 'DOUBLE',
        );

        if (isset($overrides['status'])) {
            match ($overrides['status']) {
                'confirmed' => $reservation->confirm(),
                'checked_in' => (function () use ($reservation, $overrides) {
                    $reservation->confirm();
                    $reservation->checkIn($overrides['room_number'] ?? '101');
                })(),
                'checked_out' => (function () use ($reservation, $overrides) {
                    $reservation->confirm();
                    $reservation->checkIn($overrides['room_number'] ?? '101');
                    $reservation->checkOut();
                })(),
                'cancelled' => $reservation->cancel('Test cancellation'),
                default => null,
            };
        }

        $repository->save($reservation);

        return $reservation;
    }

    private function createRoom(string $number = '101', string $type = 'DOUBLE'): void
    {
        $repository = $this->app->make(RoomRepository::class);

        $room = Room::create(
            uuid: $repository->nextIdentity(),
            number: $number,
            type: RoomType::from($type),
            floor: (int) substr($number, 0, 1),
            capacity: 2,
            pricePerNight: 250.00,
            amenities: ['wifi'],
            createdAt: new DateTimeImmutable,
        );

        $repository->save($room);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    #[Test]
    public function it_blocks_guest_actors(): void
    {
        $guest = $this->createGuestActor();

        $this->actingAs($guest)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    #[Test]
    public function it_shows_dashboard_with_empty_stats(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('guestStats')
            ->has('reservationStats')
            ->has('roomStats')
            ->where('guestStats.total', 0)
            ->where('reservationStats.total', 0)
            ->where('reservationStats.today_check_ins', 0)
            ->where('reservationStats.today_check_outs', 0)
            ->where('roomStats.total', 0)
        );
    }

    #[Test]
    public function it_shows_correct_guest_totals(): void
    {
        $this->createGuest();
        $this->createGuest();
        $this->createGuest();

        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('guestStats.total', 3)
        );
    }

    #[Test]
    public function it_shows_guests_by_loyalty_tier(): void
    {
        $this->createGuest(['loyalty_tier' => 'bronze']);
        $this->createGuest(['loyalty_tier' => 'bronze']);
        $this->createGuest(['loyalty_tier' => 'gold']);
        $this->createGuest(['loyalty_tier' => 'platinum']);

        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('guestStats.by_loyalty_tier.bronze', 2)
            ->where('guestStats.by_loyalty_tier.gold', 1)
            ->where('guestStats.by_loyalty_tier.platinum', 1)
        );
    }

    #[Test]
    public function it_shows_reservation_totals(): void
    {
        $guestId = $this->createGuest();
        $this->createReservation($guestId);
        $this->createReservation($guestId);

        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('reservationStats.total', 2)
        );
    }

    #[Test]
    public function it_shows_reservations_by_status(): void
    {
        $guestId = $this->createGuest();
        $this->createReservation($guestId);
        $this->createReservation($guestId, ['status' => 'confirmed']);
        $this->createReservation($guestId, ['status' => 'confirmed']);
        $this->createReservation($guestId, ['status' => 'cancelled']);

        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('reservationStats.by_status.pending', 1)
            ->where('reservationStats.by_status.confirmed', 2)
            ->where('reservationStats.by_status.cancelled', 1)
        );
    }

    #[Test]
    public function it_shows_reservations_by_room_type(): void
    {
        $guestId = $this->createGuest();
        $this->createReservation($guestId, ['room_type' => 'SINGLE']);
        $this->createReservation($guestId, ['room_type' => 'DOUBLE']);
        $this->createReservation($guestId, ['room_type' => 'DOUBLE']);
        $this->createReservation($guestId, ['room_type' => 'SUITE']);

        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('reservationStats.by_room_type.SINGLE', 1)
            ->where('reservationStats.by_room_type.DOUBLE', 2)
            ->where('reservationStats.by_room_type.SUITE', 1)
        );
    }

    #[Test]
    public function it_shows_room_stats(): void
    {
        $this->createRoom('101', 'SINGLE');
        $this->createRoom('201', 'DOUBLE');
        $this->createRoom('202', 'DOUBLE');
        $this->createRoom('301', 'SUITE');

        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('roomStats.total', 4)
            ->where('roomStats.by_type.SINGLE', 1)
            ->where('roomStats.by_type.DOUBLE', 2)
            ->where('roomStats.by_type.SUITE', 1)
            ->where('roomStats.by_status.available', 4)
        );
    }
}
