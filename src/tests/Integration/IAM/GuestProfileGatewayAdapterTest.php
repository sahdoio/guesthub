<?php

declare(strict_types=1);

namespace Tests\Integration\IAM;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\IAM\Domain\Service\GuestProfileGateway;
use Modules\IAM\Infrastructure\Integration\GuestProfileGatewayAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(GuestProfileGatewayAdapter::class)]
final class GuestProfileGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private GuestProfileGateway $gateway;
    private GuestProfileRepository $guestRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = $this->app->make(GuestProfileGateway::class);
        $this->guestRepo = $this->app->make(GuestProfileRepository::class);
    }

    #[Test]
    public function itCreatesAGuestProfileAndReturnsUuid(): void
    {
        $uuid = $this->gateway->create(
            name: 'Alice Johnson',
            email: 'alice@hotel.com',
            phone: '+5511999999999',
            document: 'ABC123',
        );

        $this->assertNotEmpty($uuid);
        $this->assertIsString($uuid);

        $this->assertDatabaseHas('guest_profiles', [
            'uuid' => $uuid,
            'full_name' => 'Alice Johnson',
            'email' => 'alice@hotel.com',
            'phone' => '+5511999999999',
            'document' => 'ABC123',
            'loyalty_tier' => 'bronze',
        ]);
    }

    #[Test]
    public function itCreatesProfileReadableByGuestRepository(): void
    {
        $uuid = $this->gateway->create(
            name: 'Bob Williams',
            email: 'bob@hotel.com',
            phone: '+5511888888888',
            document: 'DEF456',
        );

        $profile = $this->guestRepo->findByEmail('bob@hotel.com');

        $this->assertNotNull($profile);
        $this->assertSame($uuid, (string) $profile->uuid);
        $this->assertSame('Bob Williams', $profile->fullName);
    }
}
