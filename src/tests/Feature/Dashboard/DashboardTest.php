<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
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
        $this->actor = $this->createOwnerActor();
    }

    private function createGuest(array $overrides = []): string
    {
        $repository = $this->app->make(UserRepository::class);

        $guest = User::create(
            uuid: $repository->nextIdentity(),
            fullName: $overrides['full_name'] ?? 'Jane Doe',
            email: $overrides['email'] ?? 'jane'.uniqid().'@hotel.com',
            phone: $overrides['phone'] ?? '5511999999999',
            document: $overrides['document'] ?? 'DOC'.uniqid(),
            loyaltyTier: LoyaltyTier::from($overrides['loyalty_tier'] ?? 'bronze'),
            preferences: [],
            createdAt: new DateTimeImmutable,
            hashedPassword: 'hashed_default',
            actorType: 'guest',
            emailUniquenessChecker: $this->app->make(\Modules\IAM\Domain\Service\UserEmailUniquenessChecker::class),
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
            accountId: $this->account->uuid,
            stayId: $this->stay->uuid,
            period: new ReservationPeriod(
                new DateTimeImmutable($overrides['check_in'] ?? '+1 day'),
                new DateTimeImmutable($overrides['check_out'] ?? '+3 days'),
            ),
        );

        if (isset($overrides['status'])) {
            match ($overrides['status']) {
                'confirmed' => $reservation->confirm(),
                'checked_in' => (function () use ($reservation) {
                    $reservation->confirm();
                    $reservation->checkIn();
                })(),
                'checked_out' => (function () use ($reservation) {
                    $reservation->confirm();
                    $reservation->checkIn();
                    $reservation->checkOut();
                })(),
                'cancelled' => $reservation->cancel('Test cancellation'),
                default => null,
            };
        }

        $repository->save($reservation, $this->account->id);

        return $reservation;
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
            ->has('stayStats')
            ->has('billingStats')
            ->where('guestStats.total', 0)
            ->where('reservationStats.total', 0)
            ->where('reservationStats.today_check_ins', 0)
            ->where('reservationStats.today_check_outs', 0)
            ->where('stayStats.total', 1)
            ->where('billingStats.total', 0)
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
    public function it_shows_stay_stats(): void
    {
        $response = $this->actingAs($this->actor)
            ->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('stayStats')
            ->where('stayStats.total', 1)
        );
    }
}
