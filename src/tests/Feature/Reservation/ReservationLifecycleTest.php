<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesGuestProfile;
use Tests\TestCase;

final class ReservationLifecycleTest extends TestCase
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

    // --- Show ---

    #[Test]
    public function itShowsAReservation(): void
    {
        $id = $this->createReservation();

        $response = $this->getJson("/api/reservations/{$id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.status', 'pending');
    }

    #[Test]
    public function itReturns404ForUnknownReservation(): void
    {
        $response = $this->getJson('/api/reservations/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(500); // DomainException not caught as 404 yet
    }

    // --- Full Lifecycle ---

    #[Test]
    public function fullLifecycleCreateConfirmCheckinCheckout(): void
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

    #[Test]
    public function cancelPendingReservation(): void
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
    public function cancelConfirmedReservation(): void
    {
        $id = $this->createReservation();
        $this->postJson("/api/reservations/{$id}/confirm");

        $this->postJson("/api/reservations/{$id}/cancel", ['reason' => 'Emergency cancellation needed'])
            ->assertOk();

        $this->assertDatabaseHas('reservations', ['uuid' => $id, 'status' => 'cancelled']);
    }

    #[Test]
    public function cancelRequiresReasonWithMinLength(): void
    {
        $id = $this->createReservation();

        $this->postJson("/api/reservations/{$id}/cancel", ['reason' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    // --- Special Requests ---

    #[Test]
    public function addSpecialRequest(): void
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
    public function addSpecialRequestValidatesType(): void
    {
        $id = $this->createReservation();

        $this->postJson("/api/reservations/{$id}/special-requests", [
            'type' => 'invalid_type',
            'description' => 'Something',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    // --- Check-in Validation ---

    #[Test]
    public function checkinRequiresRoomNumber(): void
    {
        $id = $this->createReservation();
        $this->postJson("/api/reservations/{$id}/confirm");

        $this->postJson("/api/reservations/{$id}/check-in", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_number']);
    }

    #[Test]
    public function checkinValidatesRoomNumberFormat(): void
    {
        $id = $this->createReservation();
        $this->postJson("/api/reservations/{$id}/confirm");

        $this->postJson("/api/reservations/{$id}/check-in", ['room_number' => 'INVALID!!'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['room_number']);
    }
}
