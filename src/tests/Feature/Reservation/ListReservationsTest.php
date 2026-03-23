<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesGuest;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class ListReservationsTest extends TestCase
{
    use CreatesGuest;
    use RefreshDatabase;
    use SeedsRolesAndAccount;

    private string $guestId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndAccount();

        Sanctum::actingAs($this->createOwnerActor());

        $this->guestId = $this->createGuest();
    }

    private function createReservation(array $overrides = []): string
    {
        $payload = array_merge([
            'guest_id' => $this->guestId,
            'stay_id' => $this->stay->uuid,
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
        ], $overrides);

        $response = $this->postJson('/api/reservations', $payload);
        $response->assertStatus(201);

        return $response->json('data.id');
    }

    #[Test]
    public function it_returns_empty_list(): void
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
    public function it_lists_reservations(): void
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
    public function it_paginates_results(): void
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
    public function it_returns_second_page(): void
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
    public function each_item_has_reservation_structure(): void
    {
        $this->createReservation();

        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'status',
                        'guest' => ['guest_id', 'full_name', 'email', 'phone', 'document', 'is_vip'],
                        'period' => ['check_in', 'check_out', 'nights'],
                        'special_requests',
                        'timestamps' => ['created_at'],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_filtersby_status(): void
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
    public function it_caps_per_page_at100(): void
    {
        $response = $this->getJson('/api/reservations?per_page=999');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    #[Test]
    public function it_defaults_to15_per_page(): void
    {
        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 15);
    }
}
