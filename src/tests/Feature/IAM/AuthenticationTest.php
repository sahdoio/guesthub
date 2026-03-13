<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
    public function itRegistersAGuestActor(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@hotel.com')
            ->assertJsonPath('data.roles.0', 'guest');

        $this->assertDatabaseHas('actors', [
            'email' => 'john@hotel.com',
        ]);

        $guestUuid = $response->json('data.guest_id');
        $this->assertNotNull($guestUuid);

        $this->assertDatabaseHas('guests', [
            'uuid' => $guestUuid,
            'full_name' => 'John Doe',
            'email' => 'john@hotel.com',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ]);

        // subject_id in actors is a numeric FK, verify via the guests table
        $guestNumericId = \Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel::where('uuid', $guestUuid)->value('id');
        $this->assertDatabaseHas('actors', [
            'email' => 'john@hotel.com',
            'subject_id' => $guestNumericId,
        ]);
    }

    #[Test]
    public function itPreventsDuplicateEmailRegistration(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ])->assertStatus(201);

        $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'john@hotel.com',
            'password' => 'password456',
            'phone' => '+5511888888888',
            'document' => 'DEF789012',
        ])->assertStatus(500);
    }

    #[Test]
    public function itValidatesRegistrationFields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'phone', 'document']);
    }

    #[Test]
    public function itValidatesPasswordMinLength(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John',
            'email' => 'john@hotel.com',
            'password' => 'short',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function itLogsInAndReturnsToken(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ])->assertStatus(201);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@hotel.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'actor_id']);

        $this->assertNotEmpty($response->json('token'));
    }

    #[Test]
    public function itRejectsInvalidCredentials(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@hotel.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(500);
    }

    #[Test]
    public function itLogsOutAndRevokesTokens(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@hotel.com',
            'password' => 'password123',
            'phone' => '+5511999999999',
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
    public function logoutRequiresAuthentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    #[Test]
    public function protectedRoutesRequireAuthentication(): void
    {
        $this->getJson('/api/reservations')->assertStatus(401);
        $this->getJson('/api/guests')->assertStatus(401);
    }

    // --- Web Login (Admin Access) ---

    #[Test]
    public function adminCanLoginViaWeb(): void
    {
        $this->createAdminActor([
            'email' => 'admin@guesthub.com',
        ]);

        $this->post('/login', [
            'email' => 'admin@guesthub.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }

    #[Test]
    public function guestRoleCannotLoginViaWeb(): void
    {
        $this->createGuestActor([
            'email' => 'guest@hotel.com',
        ]);

        $this->post('/login', [
            'email' => 'guest@hotel.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    #[Test]
    public function guestRoleCannotAccessWebRoutes(): void
    {
        $guest = $this->createGuestActor([
            'email' => 'guest@hotel.com',
        ]);

        $this->actingAs($guest)->get('/dashboard')->assertRedirect('/login');
        $this->actingAs($guest)->get('/reservations')->assertRedirect('/login');
        $this->actingAs($guest)->get('/guests')->assertRedirect('/login');
        $this->actingAs($guest)->get('/rooms')->assertRedirect('/login');
    }
}
