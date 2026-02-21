<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesGuestProfile;
use Tests\TestCase;

final class ListReservationsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesGuestProfile;

    private string $guestProfileId;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(ActorModel::create([
            'uuid' => \Ramsey\Uuid\Uuid::uuid7()->toString(),
            'type' => 'system',
            'name' => 'Test System',
            'email' => 'system@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ]));

        $this->guestProfileId = $this->createGuestProfile();
    }

    private function createReservation(array $overrides = []): string
    {
        $payload = array_merge([
            'guest_profile_id' => $this->guestProfileId,
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'room_type' => 'DOUBLE',
        ], $overrides);

        $response = $this->postJson('/api/reservations', $payload);
        $response->assertStatus(201);

        return $response->json('data.id');
    }

    #[Test]
    public function itReturnsEmptyList(): void
    {
        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.current_page', 1);
    }

    #[Test]
    public function itListsReservations(): void
    {
        $this->createReservation();
        $this->createReservation();
        $this->createReservation();

        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function itPaginatesResults(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createReservation();
        }

        $response = $this->getJson('/api/reservations?per_page=2&page=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3);
    }

    #[Test]
    public function itReturnsSecondPage(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createReservation();
        }

        $page1 = $this->getJson('/api/reservations?per_page=2&page=1');
        $page2 = $this->getJson('/api/reservations?per_page=2&page=2');

        $page1->assertJsonCount(2, 'data');
        $page2->assertJsonCount(2, 'data');

        $page1Ids = collect($page1->json('data'))->pluck('id')->all();
        $page2Ids = collect($page2->json('data'))->pluck('id')->all();

        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    #[Test]
    public function eachItemHasReservationStructure(): void
    {
        $this->createReservation();

        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'status',
                        'guest' => ['guest_profile_id', 'full_name', 'email', 'phone', 'document', 'is_vip'],
                        'period' => ['check_in', 'check_out', 'nights'],
                        'room_type',
                        'assigned_room_number',
                        'special_requests',
                        'timestamps' => ['created_at'],
                    ],
                ],
            ]);
    }

    #[Test]
    public function itFiltersbyStatus(): void
    {
        $id = $this->createReservation();
        $this->createReservation();

        $this->postJson("/api/reservations/{$id}/confirm");

        $response = $this->getJson('/api/reservations?status=confirmed');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'confirmed');
    }

    #[Test]
    public function itFiltersByRoomType(): void
    {
        $this->createReservation(['room_type' => 'DOUBLE']);
        $this->createReservation(['room_type' => 'SUITE']);
        $this->createReservation(['room_type' => 'DOUBLE']);

        $response = $this->getJson('/api/reservations?room_type=SUITE');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.room_type', 'SUITE');
    }

    #[Test]
    public function itCombinesFilters(): void
    {
        $id = $this->createReservation(['room_type' => 'DOUBLE']);
        $this->createReservation(['room_type' => 'SUITE']);
        $this->createReservation(['room_type' => 'DOUBLE']);

        $this->postJson("/api/reservations/{$id}/confirm");

        $response = $this->getJson('/api/reservations?status=confirmed&room_type=DOUBLE');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'confirmed')
            ->assertJsonPath('data.0.room_type', 'DOUBLE');
    }

    #[Test]
    public function itCapsPerPageAt100(): void
    {
        $response = $this->getJson('/api/reservations?per_page=999');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    #[Test]
    public function itDefaultsTo15PerPage(): void
    {
        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 15);
    }
}
