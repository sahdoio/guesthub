<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Tests\TestCase;

final class CreateReservationTest extends TestCase
{
    use RefreshDatabase;

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
        ]));

        $response = $this->postJson('/api/guests', [
            'full_name' => 'John Doe',
            'email' => 'john@hotel.com',
            'phone' => '+5511999999999',
            'document' => '12345678900',
            'loyalty_tier' => 'bronze',
        ]);

        $this->guestProfileId = $response->json('data.id');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'guest_profile_id' => $this->guestProfileId,
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'room_type' => 'DOUBLE',
        ], $overrides);
    }

    public function test_it_creates_a_reservation(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'guest' => ['guest_profile_id', 'full_name', 'email', 'phone', 'document', 'is_vip'],
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
            'guest_profile_id' => $this->guestProfileId,
            'status' => 'pending',
            'room_type' => 'DOUBLE',
        ]);
    }

    public function test_it_creates_a_vip_reservation(): void
    {
        // Update guest to gold tier for VIP
        $this->putJson("/api/guests/{$this->guestProfileId}", [
            'loyalty_tier' => 'gold',
        ]);

        $response = $this->postJson('/api/reservations', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.guest.is_vip', true);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'guest_profile_id',
                'check_in',
                'check_out',
                'room_type',
            ]);
    }

    public function test_it_validates_guest_profile_id_format(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'guest_profile_id' => 'not-a-uuid',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guest_profile_id']);
    }

    public function test_it_validates_checkin_not_in_past(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'check_in' => now()->subDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in']);
    }

    public function test_it_validates_checkout_after_checkin(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'check_in' => now()->addDays(5)->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_out']);
    }

    public function test_it_validates_room_type(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'room_type' => 'PENTHOUSE',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['room_type']);
    }
}
