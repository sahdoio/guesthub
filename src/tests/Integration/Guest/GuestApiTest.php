<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Infrastructure\Integration\Dto\GuestData;
use Modules\Guest\Infrastructure\Integration\GuestApi;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

#[CoversClass(GuestApi::class)]
final class GuestApiTest extends TestCase
{
    use RefreshDatabase;

    private GuestApi $api;

    private GuestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Hotel',
            'created_at' => now(),
        ]);
        $this->app->make(TenantContext::class)->set($account->id);

        $this->api = $this->app->make(GuestApi::class);
        $this->repository = $this->app->make(GuestRepository::class);
    }

    #[Test]
    public function it_creates_a_guest_profile_and_returns_numeric_id(): void
    {
        $id = $this->api->create(
            name: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123',
        );

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $profile = $this->repository->findByEmail('alice@hotel.com');
        $this->assertNotNull($profile);
        $this->assertSame('Alice Johnson', $profile->fullName);
    }

    #[Test]
    public function it_finds_guest_profile_by_uuid(): void
    {
        $this->api->create(
            name: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123',
        );

        $profile = $this->repository->findByEmail('alice@hotel.com');
        $uuid = (string) $profile->uuid;

        $data = $this->api->findByUuid($uuid);

        $this->assertInstanceOf(GuestData::class, $data);
        $this->assertSame($uuid, $data->uuid);
        $this->assertSame('Alice Johnson', $data->fullName);
        $this->assertSame('alice@hotel.com', $data->email);
        $this->assertSame('+5511999999999', $data->phone);
        $this->assertSame('ABC123', $data->document);
        $this->assertSame('bronze', $data->loyaltyTier);
    }

    #[Test]
    public function it_returns_null_for_unknown_uuid(): void
    {
        $data = $this->api->findByUuid('00000000-0000-0000-0000-000000000000');

        $this->assertNull($data);
    }
}
