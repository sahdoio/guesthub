<?php

declare(strict_types=1);

namespace Tests\Feature\Reservation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListReservationsTest extends TestCase
{
    use RefreshDatabase;

    private function createReservation(array $overrides = []): string
    {
        $payload = array_merge([
            'guest_full_name' => 'John Doe',
            'guest_email' => 'john@hotel.com',
            'guest_phone' => '+5511999999999',
            'guest_document' => '12345678900',
            'is_vip' => false,
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(4)->format('Y-m-d'),
            'room_type' => 'DOUBLE',
        ], $overrides);

        $response = $this->postJson('/api/reservations', $payload);
        $response->assertStatus(201);

        return $response->json('data.id');
    }

    public function test_it_returns_empty_list(): void
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

    public function test_it_lists_reservations(): void
    {
        $this->createReservation(['guest_full_name' => 'Alice']);
        $this->createReservation(['guest_full_name' => 'Bob', 'guest_email' => 'bob@hotel.com']);
        $this->createReservation(['guest_full_name' => 'Carol', 'guest_email' => 'carol@hotel.com']);

        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_it_paginates_results(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createReservation([
                'guest_full_name' => "Guest {$i}",
                'guest_email' => "guest{$i}@hotel.com",
            ]);
        }

        $response = $this->getJson('/api/reservations?per_page=2&page=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_it_returns_second_page(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createReservation([
                'guest_full_name' => "Guest {$i}",
                'guest_email' => "guest{$i}@hotel.com",
            ]);
        }

        $page1 = $this->getJson('/api/reservations?per_page=2&page=1');
        $page2 = $this->getJson('/api/reservations?per_page=2&page=2');

        $page1->assertJsonCount(2, 'data');
        $page2->assertJsonCount(2, 'data');

        $page1Ids = collect($page1->json('data'))->pluck('id')->all();
        $page2Ids = collect($page2->json('data'))->pluck('id')->all();

        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    public function test_each_item_has_reservation_structure(): void
    {
        $this->createReservation();

        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'status',
                        'guest' => ['full_name', 'email', 'phone', 'document', 'is_vip'],
                        'period' => ['check_in', 'check_out', 'nights'],
                        'room_type',
                        'assigned_room_number',
                        'special_requests',
                        'timestamps' => ['created_at'],
                    ],
                ],
            ]);
    }

    public function test_it_caps_per_page_at_100(): void
    {
        $response = $this->getJson('/api/reservations?per_page=999');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_it_defaults_to_15_per_page(): void
    {
        $response = $this->getJson('/api/reservations');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 15);
    }
}
