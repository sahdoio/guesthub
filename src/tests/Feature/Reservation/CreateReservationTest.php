<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesGuest;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class CreateReservationTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'guest_id' => $this->guestId,
            'stay_id' => $this->stay->uuid,
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
        ], $overrides);
    }

    #[Test]
    public function itCreatesAReservation(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'guest' => ['guest_id', 'full_name', 'email', 'phone', 'document', 'is_vip'],
                    'period' => ['check_in', 'check_out', 'nights'],
                    'special_requests',
                    'timestamps' => ['created_at'],
                ],
            ])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.guest.full_name', 'John Doe');

        $this->assertDatabaseHas('reservations', [
            'guest_id' => $this->guestId,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function itCreatesAVipReservation(): void
    {
        $this->putJson("/api/guests/{$this->guestId}", [
            'loyalty_tier' => 'gold',
        ]);

        $response = $this->postJson('/api/reservations', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.guest.is_vip', true);
    }

    #[Test]
    public function itValidatesRequiredFields(): void
    {
        $response = $this->postJson('/api/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'guest_id',
                'stay_id',
                'check_in',
                'check_out',
            ]);
    }

    #[Test]
    public function itValidatesGuestIdFormat(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'guest_id' => 'not-a-uuid',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guest_id']);
    }

    #[Test]
    public function itValidatesCheckinNotInPast(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'check_in' => now()->subDay()->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_in']);
    }

    #[Test]
    public function itValidatesCheckoutAfterCheckin(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'check_in' => now()->addDays(5)->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_out']);
    }

    #[Test]
    public function itValidatesStayIdFormat(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'stay_id' => 'not-a-uuid',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stay_id']);
    }
}
