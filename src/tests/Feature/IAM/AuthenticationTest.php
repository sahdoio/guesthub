<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndAccount();
    }

    #[Test]
    public function it_registers_a_guest_actor(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '5511999999999',
            'document' => 'ABC123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@hotel.com')
            ->assertJsonPath('data.roles.0', 'guest');

        $this->assertDatabaseHas('actors', [
            'email' => 'john@hotel.com',
        ]);

        // Guest should have their own personal account
        $actorAccountId = \Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel::where('email', 'john@hotel.com')->value('account_id');
        $this->assertNotNull($actorAccountId);
        $this->assertDatabaseHas('accounts', ['id' => $actorAccountId]);

        $guestUuid = $response->json('data.guest_id');
        $this->assertNotNull($guestUuid);

        $this->assertDatabaseHas('users', [
            'uuid' => $guestUuid,
            'full_name' => 'John Doe',
            'email' => 'john@hotel.com',
            'phone' => '5511999999999',
            'document' => 'ABC123456',
        ]);

        // user_id in actors is a numeric FK, verify via the users table
        $userNumericId = \Modules\IAM\Infrastructure\Persistence\Eloquent\UserModel::where('uuid', $guestUuid)->value('id');
        $this->assertDatabaseHas('actors', [
            'email' => 'john@hotel.com',
            'user_id' => $userNumericId,
        ]);
    }

    #[Test]
    public function it_prevents_duplicate_email_registration(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '5511999999999',
            'document' => 'ABC123456',
        ])->assertStatus(201);

        $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'john@hotel.com',
            'password' => 'password456',
            'phone' => '5511888888888',
            'document' => 'DEF789012',
        ])->assertStatus(500);
    }

    #[Test]
    public function it_validates_registration_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'phone', 'document']);
    }

    #[Test]
    public function it_validates_password_min_length(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'john@hotel.com',
            'password' => 'short',
            'phone' => '5511999999999',
            'document' => 'ABC123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function it_logs_in_and_returns_token(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '5511999999999',
            'document' => 'ABC123456',
        ])->assertStatus(201);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@hotel.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'actorId']);

        $this->assertNotEmpty($response->json('token'));
    }

    #[Test]
    public function it_rejects_invalid_credentials(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '5511999999999',
            'document' => 'ABC123456',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@hotel.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(500);
    }

    #[Test]
    public function it_logs_out_and_revokes_tokens(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '5511999999999',
            'document' => 'ABC123456',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'john@hotel.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');
    }

    #[Test]
    public function logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    #[Test]
    public function protected_routes_require_authentication(): void
    {
        $this->getJson('/api/reservations')->assertStatus(401);
        $this->getJson('/api/guests')->assertStatus(401);
    }

    // --- Web Login ---

    #[Test]
    public function owner_can_login_via_web(): void
    {
        $this->createOwnerActor([
            'email' => 'owner@guesthub.com',
        ]);

        $this->post('/login', [
            'email' => 'owner@guesthub.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }

    #[Test]
    public function superadmin_login_redirects_to_superadmin_home(): void
    {
        $this->createSuperAdminActor([
            'email' => 'super@guesthub.com',
        ]);

        $this->post('/login', [
            'email' => 'super@guesthub.com',
            'password' => 'password',
        ])->assertRedirect('/superadmin');
    }

    #[Test]
    public function guest_role_redirects_to_portal_on_web_login(): void
    {
        $this->createGuestActor([
            'email' => 'guest@hotel.com',
        ]);

        $this->post('/login', [
            'email' => 'guest@hotel.com',
            'password' => 'password',
        ])->assertRedirect('/portal');
    }

    #[Test]
    public function guest_role_cannot_access_owner_web_routes(): void
    {
        $guest = $this->createGuestActor([
            'email' => 'guest@hotel.com',
        ]);

        $this->actingAs($guest)->get('/dashboard')->assertRedirect('/login');
        $this->actingAs($guest)->get('/reservations')->assertRedirect('/login');
        $this->actingAs($guest)->get('/guests')->assertRedirect('/login');
        $this->actingAs($guest)->get('/stays')->assertRedirect('/login');
    }
}
