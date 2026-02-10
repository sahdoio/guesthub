<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateReservationTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'guest_full_name' => 'John Doe',
            'guest_email' => 'john@hotel.com',
            'guest_phone' => '+5511999999999',
            'guest_document' => '12345678900',
            'is_vip' => false,
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
                    'guest' => ['full_name', 'email', 'phone', 'document', 'is_vip'],
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
            'guest_email' => 'john@hotel.com',
            'status' => 'pending',
            'room_type' => 'DOUBLE',
        ]);
    }

    public function test_it_creates_a_vip_reservation(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload(['is_vip' => true]));

        $response->assertStatus(201)
            ->assertJsonPath('data.guest.is_vip', true);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/reservations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'guest_full_name',
                'guest_email',
                'guest_phone',
                'guest_document',
                'check_in',
                'check_out',
                'room_type',
            ]);
    }

    public function test_it_validates_email_format(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'guest_email' => 'not-an-email',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guest_email']);
    }

    public function test_it_validates_phone_format(): void
    {
        $response = $this->postJson('/api/reservations', $this->validPayload([
            'guest_phone' => '123456',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guest_phone']);
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
