<?php

declare(strict_types=1);

namespace Tests\Feature\Guest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GuestProfileCrudTest extends TestCase
{
    use RefreshDatabase;

    private function createGuest(array $overrides = []): string
    {
        $payload = array_merge([
            'full_name' => 'Jane Doe',
            'email' => 'jane@hotel.com',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
            'loyalty_tier' => 'bronze',
            'preferences' => ['late_checkout', 'high_floor'],
        ], $overrides);

        $response = $this->postJson('/api/guests', $payload);
        $response->assertStatus(201);

        return $response->json('data.id');
    }

    // --- Create ---

    public function test_it_creates_a_guest_profile(): void
    {
        $response = $this->postJson('/api/guests', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@hotel.com',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
            'loyalty_tier' => 'gold',
            'preferences' => ['ocean_view'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.full_name', 'Jane Doe')
            ->assertJsonPath('data.email', 'jane@hotel.com')
            ->assertJsonPath('data.loyalty_tier', 'gold')
            ->assertJsonPath('data.preferences', ['ocean_view']);

        $this->assertDatabaseHas('guest_profiles', [
            'uuid' => $response->json('data.id'),
            'full_name' => 'Jane Doe',
            'email' => 'jane@hotel.com',
        ]);
    }

    public function test_it_creates_guest_with_default_loyalty_tier(): void
    {
        $response = $this->postJson('/api/guests', [
            'full_name' => 'Bob Smith',
            'email' => 'bob@hotel.com',
            'phone' => '+5521888888888',
            'document' => 'XYZ987654',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.loyalty_tier', 'bronze');
    }

    public function test_create_validates_required_fields(): void
    {
        $this->postJson('/api/guests', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'email', 'phone', 'document']);
    }

    public function test_create_validates_email_format(): void
    {
        $this->postJson('/api/guests', [
            'full_name' => 'Jane Doe',
            'email' => 'not-an-email',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_validates_phone_format(): void
    {
        $this->postJson('/api/guests', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@hotel.com',
            'phone' => '11999999999',
            'document' => 'ABC123456',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_create_validates_loyalty_tier(): void
    {
        $this->postJson('/api/guests', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@hotel.com',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
            'loyalty_tier' => 'diamond',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['loyalty_tier']);
    }

    // --- Show ---

    public function test_it_shows_a_guest_profile(): void
    {
        $id = $this->createGuest();

        $response = $this->getJson("/api/guests/{$id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.full_name', 'Jane Doe');
    }

    public function test_show_returns_error_for_unknown_guest(): void
    {
        $this->getJson('/api/guests/00000000-0000-0000-0000-000000000000')
            ->assertStatus(500);
    }

    // --- Update ---

    public function test_it_updates_contact_info(): void
    {
        $id = $this->createGuest();

        $response = $this->putJson("/api/guests/{$id}", [
            'full_name' => 'Jane Smith',
            'email' => 'jane.smith@hotel.com',
            'phone' => '+5521888888888',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.full_name', 'Jane Smith')
            ->assertJsonPath('data.email', 'jane.smith@hotel.com')
            ->assertJsonPath('data.phone', '+5521888888888');

        $this->assertDatabaseHas('guest_profiles', [
            'uuid' => $id,
            'full_name' => 'Jane Smith',
        ]);
    }

    public function test_it_updates_loyalty_tier(): void
    {
        $id = $this->createGuest();

        $response = $this->putJson("/api/guests/{$id}", [
            'loyalty_tier' => 'platinum',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.loyalty_tier', 'platinum');
    }

    public function test_it_updates_preferences(): void
    {
        $id = $this->createGuest();

        $response = $this->putJson("/api/guests/{$id}", [
            'preferences' => ['king_bed', 'minibar', 'ocean_view'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.preferences', ['king_bed', 'minibar', 'ocean_view']);
    }

    public function test_update_validates_email_format(): void
    {
        $id = $this->createGuest();

        $this->putJson("/api/guests/{$id}", ['email' => 'bad-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // --- Delete ---

    public function test_it_deletes_a_guest_profile(): void
    {
        $id = $this->createGuest();

        $this->deleteJson("/api/guests/{$id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('guest_profiles', ['uuid' => $id]);
    }

    public function test_delete_returns_error_for_unknown_guest(): void
    {
        $this->deleteJson('/api/guests/00000000-0000-0000-0000-000000000000')
            ->assertStatus(500);
    }

    // --- List ---

    public function test_it_lists_guest_profiles_with_pagination(): void
    {
        $this->createGuest(['document' => 'DOC001', 'email' => 'a@hotel.com']);
        $this->createGuest(['document' => 'DOC002', 'email' => 'b@hotel.com']);
        $this->createGuest(['document' => 'DOC003', 'email' => 'c@hotel.com']);

        $response = $this->getJson('/api/guests?page=1&per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_it_returns_empty_list_when_no_guests(): void
    {
        $response = $this->getJson('/api/guests');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
