<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\IAM\Domain\Service\TokenManager;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\IAM\Infrastructure\Services\SanctumTokenManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\SeedsRolesAndAccount;
use Tests\TestCase;

#[CoversClass(SanctumTokenManager::class)]
final class SanctumTokenManagerTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRolesAndAccount;

    private TokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndAccount();
        $this->tokenManager = $this->app->make(TokenManager::class);
    }

    private function createActorModel(string $email = 'john@hotel.com'): ActorModel
    {
        $actor = ActorModel::create([
            'uuid' => \Ramsey\Uuid\Uuid::uuid7()->toString(),
            'account_id' => $this->account->id,
            'name' => 'John Doe',
            'email' => $email,
            'password' => bcrypt('password123'),
            'created_at' => now(),
        ]);

        DB::table('actor_type_pivot')->insert([
            'actor_id' => $actor->id,
            'type_id' => $this->guestType->id,
        ]);

        return $actor;
    }

    #[Test]
    public function it_creates_a_token(): void
    {
        $this->createActorModel();

        $token = $this->tokenManager->createToken('john@hotel.com');

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    #[Test]
    public function it_revokes_all_tokens(): void
    {
        $model = $this->createActorModel();
        $this->tokenManager->createToken('john@hotel.com');
        $this->tokenManager->createToken('john@hotel.com', 'second-token');

        $this->assertGreaterThan(0, $model->tokens()->count());

        $this->tokenManager->revokeAllTokens('john@hotel.com');

        $this->assertSame(0, $model->tokens()->count());
    }
}
