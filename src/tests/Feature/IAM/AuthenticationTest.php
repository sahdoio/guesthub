<?php

declare(strict_types=1);

namespace Tests\Feature\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_guest_actor(): void
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
            ->assertJsonPath('data.type', 'guest');

        $this->assertDatabaseHas('actors', [
            'email' => 'john@hotel.com',
            'type' => 'guest',
        ]);

        $guestProfileUuid = $response->json('data.guest_profile_id');
        $this->assertNotNull($guestProfileUuid);

        $this->assertDatabaseHas('guest_profiles', [
            'uuid' => $guestProfileUuid,
            'full_name' => 'John Doe',
            'email' => 'john@hotel.com',
            'phone' => '+5511999999999',
            'document' => 'ABC123456',
        ]);

        $guestProfileId = DB::table('guest_profiles')->where('uuid', $guestProfileUuid)->value('id');
        $this->assertDatabaseHas('actors', [
            'email' => 'john@hotel.com',
            'guest_profile_id' => $guestProfileId,
        ]);
    }

    public function test_it_prevents_duplicate_email_registration(): void
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

    public function test_it_validates_registration_fields(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'phone', 'document']);
    }

    public function test_it_validates_password_min_length(): void
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

    public function test_it_logs_in_and_returns_token(): void
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

    public function test_it_rejects_invalid_credentials(): void
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

    public function test_it_logs_out_and_revokes_tokens(): void
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

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/reservations')->assertStatus(401);
        $this->getJson('/api/guests')->assertStatus(401);
    }
}
