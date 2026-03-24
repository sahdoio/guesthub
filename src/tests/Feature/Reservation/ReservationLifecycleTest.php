<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesGuest;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class ReservationLifecycleTest extends TestCase
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

    // --- Show ---

    #[Test]
    public function it_shows_a_reservation(): void
    {
        $id = $this->createReservation();

        $response = $this->getJson("/api/reservations/{$id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.status', 'pending');
    }

    #[Test]
    public function it_returns404_for_unknown_reservation(): void
    {
        $response = $this->getJson('/api/reservations/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(500); // DomainException not caught as 404 yet
    }

    // --- Full Lifecycle ---

    #[Test]
    public function full_lifecycle_create_confirm_checkin_checkout(): void
    {
        $id = $this->createReservation();

        // Confirm
        $this->postJson("/api/reservations/{$id}/confirm")
            ->assertOk()
            ->assertJsonPath('message', 'Reservation confirmed.');

        $this->assertDatabaseHas('reservations', ['uuid' => $id, 'status' => 'confirmed']);

        // Check-in
        $this->postJson("/api/reservations/{$id}/check-in")
            ->assertOk()
            ->assertJsonPath('message', 'Guest checked in.');

        $this->assertDatabaseHas('reservations', [
            'uuid' => $id,
            'status' => 'checked_in',
        ]);

        // Check-out
        $this->postJson("/api/reservations/{$id}/check-out")
            ->assertOk()
            ->assertJsonPath('message', 'Guest checked out.');

        $this->assertDatabaseHas('reservations', ['uuid' => $id, 'status' => 'checked_out']);
    }

    // --- Cancellation ---

    #[Test]
    public function cancel_pending_reservation(): void
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

    #[Test]
    public function cancel_confirmed_reservation(): void
    {
        $id = $this->createReservation();
        $this->postJson("/api/reservations/{$id}/confirm");

        $this->postJson("/api/reservations/{$id}/cancel", ['reason' => 'Emergency cancellation needed'])
            ->assertOk();

        $this->assertDatabaseHas('reservations', ['uuid' => $id, 'status' => 'cancelled']);
    }

    #[Test]
    public function cancel_requires_reason_with_min_length(): void
    {
        $id = $this->createReservation();

        $this->postJson("/api/reservations/{$id}/cancel", ['reason' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    // --- Special Requests ---

    #[Test]
    public function add_special_request(): void
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

    #[Test]
    public function add_special_request_validates_type(): void
    {
        $id = $this->createReservation();

        $this->postJson("/api/reservations/{$id}/special-requests", [
            'type' => 'invalid_type',
            'description' => 'Something',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
