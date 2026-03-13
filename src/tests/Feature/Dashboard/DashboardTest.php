<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private ActorModel $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = ActorModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'type' => 'system',
            'name' => 'Test System',
            'email' => 'system@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ]);
    }

    private function createGuest(array $overrides = []): string
    {
        $repository = $this->app->make(GuestProfileRepository::class);

        $profile = GuestProfile::create(
            uuid: $repository->nextIdentity(),
            fullName: $overrides['full_name'] ?? 'Jane Doe',
            email: $overrides['email'] ?? 'jane' . uniqid() . '@hotel.com',
            phone: $overrides['phone'] ?? '+5511999999999',
            document: $overrides['document'] ?? 'DOC' . uniqid(),
            loyaltyTier: LoyaltyTier::from($overrides['loyalty_tier'] ?? 'bronze'),
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );

        $repository->save($profile);

        return (string) $profile->uuid;
    }

    private function createReservation(string $guestId, array $overrides = []): Reservation
    {
        $repository = $this->app->make(ReservationRepository::class);

        $reservation = Reservation::create(
            uuid: $repository->nextIdentity(),
            guestProfileId: $guestId,
            period: new ReservationPeriod(
                new DateTimeImmutable($overrides['check_in'] ?? '+1 day'),
                new DateTimeImmutable($overrides['check_out'] ?? '+3 days'),
            ),
            roomType: $overrides['room_type'] ?? 'DOUBLE',
        );

        if (isset($overrides['status'])) {
            match ($overrides['status']) {
                'confirmed' => $reservation->confirm(),
                'checked_in' => (function () use ($reservation) {
                    $reservation->confirm();
                    $reservation->checkIn($overrides['room_number'] ?? '101');
                })(),
                'checked_out' => (function () use ($reservation) {
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

    #[Test]
    public function itRequiresAuthentication(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    #[Test]
    public function itShowsDashboardWithEmptyStats(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('guestStats')
            ->has('reservationStats')
            ->where('guestStats.total', 0)
            ->where('reservationStats.total', 0)
            ->where('reservationStats.today_check_ins', 0)
            ->where('reservationStats.today_check_outs', 0)
        );
    }

    #[Test]
    public function itShowsCorrectGuestTotals(): void
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
    public function itShowsGuestsByLoyaltyTier(): void
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
    public function itShowsReservationTotals(): void
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
    public function itShowsReservationsByStatus(): void
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
    public function itShowsReservationsByRoomType(): void
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
}
