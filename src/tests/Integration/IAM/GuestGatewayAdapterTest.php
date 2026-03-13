<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\IAM\Domain\Service\GuestGateway;
use Modules\IAM\Infrastructure\Integration\GuestGatewayAdapter;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

#[CoversClass(GuestGatewayAdapter::class)]
final class GuestGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private GuestGateway $gateway;

    private GuestRepository $guestRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Hotel',
            'created_at' => now(),
        ]);
        $this->app->make(TenantContext::class)->set($account->id);

        $this->gateway = $this->app->make(GuestGateway::class);
        $this->guestRepo = $this->app->make(GuestRepository::class);
    }

    #[Test]
    public function it_creates_a_guest_and_returns_numeric_id(): void
    {
        $id = $this->gateway->create(
            name: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123',
        );

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $this->assertDatabaseHas('guests', [
            'id' => $id,
            'full_name' => 'Alice Johnson',
            'email' => 'alice@hotel.com',
        ]);
    }

    #[Test]
    public function it_creates_guest_readable_by_guest_repository(): void
    {
        $this->gateway->create(
            name: 'Bob Williams',
            email: 'bob@hotel.com',
            phone: '+5511888888888',
            document: 'DEF456',
        );

        $guest = $this->guestRepo->findByEmail('bob@hotel.com');

        $this->assertNotNull($guest);
        $this->assertSame('Bob Williams', $guest->fullName);
    }
}
