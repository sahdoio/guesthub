<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesGuest;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\Concerns\SeedsRooms;
use Tests\TestCase;

final class CreateReservationTest extends TestCase
{
    use CreatesGuest;
    use RefreshDatabase;
    use SeedsRolesAndAccount;
    use SeedsRooms;

    private string $guestId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndAccount();

        Sanctum::actingAs($this->createAdminActor());

        $this->guestId = $this->createGuest();
        $this->seedRooms();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'guest_id' => $this->guestId,
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'room_type' => 'DOUBLE',
        ], $overrides);
    }

    #[Test]
    public function it_creates_a_reservation(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'guest' => ['guest_id', 'full_name', 'email', 'phone', 'document', 'is_vip'],
                    'period' => ['check_in', 'check_out', 'nights'],
                    'room_type',
                    'assigned_room_number',
                    'special_requests',
                    'timestamps' => ['created_at'],
                ],
            ])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.guest.full_name', 'John Doe')
            ->assertJsonPath('data.room_type', 'DOUBLE');

        $this->assertDatabaseHas('reservations', [
            'guest_id' => $this->guestId,
            'status' => 'pending',
            'room_type' => 'DOUBLE',
        ]);
    }

    #[Test]
    public function it_creates_a_vip_reservation(): void
    {
        $this->putJson("/api/guests/{$this->guestId}", [
            'loyalty_tier' => 'gold',
        ]);

        $response = $this->postJson('/api/reservations', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.guest.is_vip', true);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'guest_id',
                'check_in',
                'check_out',
                'room_type',
            ]);
    }

    #[Test]
    public function it_validates_guest_id_format(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'guest_id' => 'not-a-uuid',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guest_id']);
    }

    #[Test]
    public function it_validates_checkin_not_in_past(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'check_in' => now()->subDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in']);
    }

    #[Test]
    public function it_validates_checkout_after_checkin(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'check_in' => now()->addDays(5)->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_out']);
    }

    #[Test]
    public function it_validates_room_type(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'room_type' => 'PENTHOUSE',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['room_type']);
    }
}
