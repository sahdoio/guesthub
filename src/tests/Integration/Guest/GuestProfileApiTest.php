<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Infrastructure\Integration\Dto\GuestProfileData;
use Modules\Guest\Infrastructure\Integration\GuestProfileApi;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(GuestProfileApi::class)]
final class GuestProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private GuestProfileApi $api;
    private GuestProfileRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->api = $this->app->make(GuestProfileApi::class);
        $this->repository = $this->app->make(GuestProfileRepository::class);
    }

    #[Test]
    public function itCreatesAGuestProfileViaHandler(): void
    {
        $uuid = $this->api->create(
            name: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123',
        );

        $this->assertNotEmpty($uuid);

        $profile = $this->repository->findByEmail('alice@hotel.com');
        $this->assertNotNull($profile);
        $this->assertSame($uuid, (string) $profile->uuid);
        $this->assertSame('Alice Johnson', $profile->fullName);
    }

    #[Test]
    public function itReturnsUuidAsString(): void
    {
        $uuid = $this->api->create(
            name: 'Bob',
            email: 'bob@hotel.com',
            phone: '+5511888888888',
            document: 'DEF456',
        );

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $uuid);
    }

    #[Test]
    public function itFindsGuestProfileByUuid(): void
    {
        $uuid = $this->api->create(
            name: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123',
        );

        $data = $this->api->findByUuid($uuid);

        $this->assertInstanceOf(GuestProfileData::class, $data);
        $this->assertSame($uuid, $data->uuid);
        $this->assertSame('Alice Johnson', $data->fullName);
        $this->assertSame('alice@hotel.com', $data->email);
        $this->assertSame('+5511999999999', $data->phone);
        $this->assertSame('ABC123', $data->document);
        $this->assertSame('bronze', $data->loyaltyTier);
    }

    #[Test]
    public function itReturnsNullForUnknownUuid(): void
    {
        $data = $this->api->findByUuid('00000000-0000-0000-0000-000000000000');

        $this->assertNull($data);
    }
}
