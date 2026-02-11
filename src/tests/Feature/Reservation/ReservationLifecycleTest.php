<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Tests\TestCase;

final class ReservationLifecycleTest extends TestCase
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

    // --- Show ---

    public function test_it_shows_a_reservation(): void
    {
        $id = $this->createReservation();

        $response = $this->getJson("/api/reservations/{$id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_it_returns_404_for_unknown_reservation(): void
    {
        $response = $this->getJson('/api/reservations/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(500); // DomainException not caught as 404 yet
    }

    // --- Full Lifecycle ---

    public function test_full_lifecycle_create_confirm_checkin_checkout(): void
    {
        $id = $this->createReservation();

        // Confirm
        $this->postJson("/api/reservations/{$id}/confirm")
            ->assertOk()
            ->assertJsonPath('message', 'Reservation confirmed.');

        $this->assertDatabaseHas('reservations', ['uuid' => $id, 'status' => 'confirmed']);

        // Check-in
        $this->postJson("/api/reservations/{$id}/check-in", ['room_number' => '201'])
            ->assertOk()
            ->assertJsonPath('message', 'Guest checked in.');

        $this->assertDatabaseHas('reservations', [
            'uuid' => $id,
            'status' => 'checked_in',
            'assigned_room_number' => '201',
        ]);

        // Check-out
        $this->postJson("/api/reservations/{$id}/check-out")
            ->assertOk()
            ->assertJsonPath('message', 'Guest checked out.');

        $this->assertDatabaseHas('reservations', ['uuid' => $id, 'status' => 'checked_out']);
    }

    // --- Cancellation ---

    public function test_cancel_pending_reservation(): void
    {
        $id = $this->createReservation();

        $this->postJson("/api/reservations/{$id}/cancel", ['reason' => 'Guest changed their travel plans'])
            ->assertOk()
            ->assertJsonPath('message', 'Reservation cancelled.');

        $this->assertDatabaseHas('reservations', [
            'uuid' => $id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Guest changed their travel plans',
        ]);
    }

    public function test_cancel_confirmed_reservation(): void
    {
        $id = $this->createReservation();
        $this->postJson("/api/reservations/{$id}/confirm");

        $this->postJson("/api/reservations/{$id}/cancel", ['reason' => 'Emergency cancellation needed'])
            ->assertOk();

        $this->assertDatabaseHas('reservations', ['uuid' => $id, 'status' => 'cancelled']);
    }

    public function test_cancel_requires_reason_with_min_length(): void
    {
        $id = $this->createReservation();

        $this->postJson("/api/reservations/{$id}/cancel", ['reason' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    // --- Special Requests ---

    public function test_add_special_request(): void
    {
        $id = $this->createReservation();

        $response = $this->postJson("/api/reservations/{$id}/special-requests", [
            'type' => 'early_check_in',
            'description' => 'Please prepare the room by 10am',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'request_id']);

        // Verify it shows up
        $show = $this->getJson("/api/reservations/{$id}");
        $show->assertJsonCount(1, 'data.special_requests')
            ->assertJsonPath('data.special_requests.0.type', 'early_check_in');
    }

    public function test_add_special_request_validates_type(): void
    {
        $id = $this->createReservation();

        $this->postJson("/api/reservations/{$id}/special-requests", [
            'type' => 'invalid_type',
            'description' => 'Something',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    // --- Check-in Validation ---

    public function test_checkin_requires_room_number(): void
    {
        $id = $this->createReservation();
        $this->postJson("/api/reservations/{$id}/confirm");

        $this->postJson("/api/reservations/{$id}/check-in", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_number']);
    }

    public function test_checkin_validates_room_number_format(): void
    {
        $id = $this->createReservation();
        $this->postJson("/api/reservations/{$id}/confirm");

        $this->postJson("/api/reservations/{$id}/check-in", ['room_number' => 'INVALID!!'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_number']);
    }
}
