<?php

declare(strict_types=1);

namespace Tests\Integration\Reservation;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\Guest;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Reservation\Domain\Service\GuestGateway;
use Modules\Reservation\Infrastructure\Integration\GuestGatewayAdapter;
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

    private function seedGuest(string $email, LoyaltyTier $tier = LoyaltyTier::BRONZE): string
    {
        $guest = Guest::create(
            uuid: $this->guestRepo->nextIdentity(),
            fullName: 'Test Guest',
            email: $email,
            phone: '+5511999999999',
            document: 'DOC-' . uniqid(),
            loyaltyTier: $tier,
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );

        $this->guestRepo->save($guest);

        return (string) $guest->uuid;
    }

    #[Test]
    public function itFindsGuestInfoByUuid(): void
    {
        $uuid = $this->seedGuest('alice@hotel.com');

        $info = $this->gateway->findByUuid($uuid);

        $this->assertNotNull($info);
        $this->assertSame($uuid, $info->guestId);
        $this->assertSame('Test Guest', $info->fullName);
        $this->assertSame('alice@hotel.com', $info->email);
        $this->assertFalse($info->isVip);
    }

    #[Test]
    public function itReturnsNullForUnknownUuid(): void
    {
        $this->assertNull($this->gateway->findByUuid('00000000-0000-0000-0000-000000000000'));
    }

    #[Test]
    public function itIdentifiesGoldGuestAsVip(): void
    {
        $uuid = $this->seedGuest('gold@hotel.com', LoyaltyTier::GOLD);

        $info = $this->gateway->findByUuid($uuid);

        $this->assertTrue($info->isVip);
    }

    #[Test]
    public function itIdentifiesPlatinumGuestAsVip(): void
    {
        $uuid = $this->seedGuest('platinum@hotel.com', LoyaltyTier::PLATINUM);

        $info = $this->gateway->findByUuid($uuid);

        $this->assertTrue($info->isVip);
    }

    #[Test]
    public function itIdentifiesSilverGuestAsNonVip(): void
    {
        $uuid = $this->seedGuest('silver@hotel.com', LoyaltyTier::SILVER);

        $info = $this->gateway->findByUuid($uuid);

        $this->assertFalse($info->isVip);
    }
}
