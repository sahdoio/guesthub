<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\UserGateway;
use Modules\IAM\Infrastructure\Integration\UserGatewayAdapter;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

#[CoversClass(UserGatewayAdapter::class)]
final class GuestGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private UserGateway $gateway;

    private UserRepository $userRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $this->app->make(TenantContext::class)->set($account->id);

        $this->gateway = $this->app->make(UserGateway::class);
        $this->userRepo = $this->app->make(UserRepository::class);
    }

    #[Test]
    public function it_creates_a_guest_and_returns_numeric_id(): void
    {
        $id = $this->gateway->create(
            name: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '5511999999999',
            document: 'ABC123',
        );

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $this->assertDatabaseHas('users', [
            'id' => $id,
            'full_name' => 'Alice Johnson',
            'email' => 'alice@hotel.com',
        ]);
    }

    #[Test]
    public function it_creates_guest_readable_by_user_repository(): void
    {
        $this->gateway->create(
            name: 'Bob Williams',
            email: 'bob@hotel.com',
            phone: '5511888888888',
            document: 'DEF456',
        );

        $user = $this->userRepo->findByEmail('bob@hotel.com');

        $this->assertNotNull($user);
        $this->assertSame('Bob Williams', $user->fullName);
    }
}
