<?php

declare(strict_types=1);

namespace Tests\Feature\Guest;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class GuestCrudTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndAccount();

        Sanctum::actingAs($this->createAdminActor());
    }

    private function createGuest(array $overrides = []): string
    {
        $repository = $this->app->make(GuestRepository::class);

        $profile = Guest::create(
            uuid: $repository->nextIdentity(),
            fullName: $overrides['full_name'] ?? 'Jane Doe',
            email: $overrides['email'] ?? 'jane@hotel.com',
            phone: $overrides['phone'] ?? '+5511999999999',
            document: $overrides['document'] ?? 'ABC123456',
            loyaltyTier: LoyaltyTier::from($overrides['loyalty_tier'] ?? 'bronze'),
            preferences: $overrides['preferences'] ?? ['late_checkout', 'high_floor'],
            createdAt: new DateTimeImmutable,
        );

        $repository->save($profile);

        return (string) $profile->uuid;
    }

    // --- Show ---

    #[Test]
    public function it_shows_a_guest_profile(): void
    {
        $id = $this->createGuest();

        $response = $this->getJson("/api/guests/{$id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.full_name', 'Jane Doe');
    }

    #[Test]
    public function show_returns_error_for_unknown_guest(): void
    {
        $this->getJson('/api/guests/00000000-0000-0000-0000-000000000000')
            ->assertStatus(500);
    }

    // --- Update ---

    #[Test]
    public function it_updates_contact_info(): void
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

        $this->assertDatabaseHas('guests', [
            'uuid' => $id,
            'full_name' => 'Jane Smith',
        ]);
    }

    #[Test]
    public function it_updates_loyalty_tier(): void
    {
        $id = $this->createGuest();

        $response = $this->putJson("/api/guests/{$id}", [
            'loyalty_tier' => 'platinum',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.loyalty_tier', 'platinum');
    }

    #[Test]
    public function it_updates_preferences(): void
    {
        $id = $this->createGuest();

        $response = $this->putJson("/api/guests/{$id}", [
            'preferences' => ['king_bed', 'minibar', 'ocean_view'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.preferences', ['king_bed', 'minibar', 'ocean_view']);
    }

    #[Test]
    public function update_validates_email_format(): void
    {
        $id = $this->createGuest();

        $this->putJson("/api/guests/{$id}", ['email' => 'bad-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // --- Delete ---

    #[Test]
    public function it_deletes_a_guest_profile(): void
    {
        $id = $this->createGuest();

        $this->deleteJson("/api/guests/{$id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('guests', ['uuid' => $id]);
    }

    #[Test]
    public function delete_returns_error_for_unknown_guest(): void
    {
        $this->deleteJson('/api/guests/00000000-0000-0000-0000-000000000000')
            ->assertStatus(500);
    }

    // --- List ---

    #[Test]
    public function it_lists_guest_profiles_with_pagination(): void
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

    #[Test]
    public function it_returns_empty_list_when_no_guests(): void
    {
        $response = $this->getJson('/api/guests');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
