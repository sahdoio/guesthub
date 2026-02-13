<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Service\TokenManager;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\IAM\Infrastructure\Security\SanctumTokenManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(SanctumTokenManager::class)]
final class SanctumTokenManagerTest extends TestCase
{
    use RefreshDatabase;

    private TokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager = $this->app->make(TokenManager::class);
    }

    private function createActorModel(string $email = 'john@hotel.com'): ActorModel
    {
        return ActorModel::create([
            'uuid' => \Ramsey\Uuid\Uuid::uuid7()->toString(),
            'type' => 'guest',
            'name' => 'John Doe',
            'email' => $email,
            'password' => bcrypt('password123'),
            'created_at' => now(),
        ]);
    }

    #[Test]
    public function itCreatesAToken(): void
    {
        $this->createActorModel();

        $token = $this->tokenManager->createToken('john@hotel.com');

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    #[Test]
    public function itRevokesAllTokens(): void
    {
        $model = $this->createActorModel();
        $this->tokenManager->createToken('john@hotel.com');
        $this->tokenManager->createToken('john@hotel.com', 'second-token');

        $this->assertGreaterThan(0, $model->tokens()->count());

        $this->tokenManager->revokeAllTokens('john@hotel.com');

        $this->assertSame(0, $model->tokens()->count());
    }
}
