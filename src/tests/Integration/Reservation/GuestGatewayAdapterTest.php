<?php

declare(strict_types=1);

namespace Tests\Integration\Reservation;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Reservation\Domain\Service\GuestGateway;
use Modules\Reservation\Infrastructure\Integration\GuestGatewayAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(GuestGatewayAdapter::class)]
final class GuestGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private GuestGateway $gateway;
    private GuestProfileRepository $guestRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = $this->app->make(GuestGateway::class);
        $this->guestRepo = $this->app->make(GuestProfileRepository::class);
    }

    private function seedGuest(string $email, LoyaltyTier $tier = LoyaltyTier::BRONZE): string
    {
        $profile = GuestProfile::create(
            uuid: $this->guestRepo->nextIdentity(),
            fullName: 'Test Guest',
            email: $email,
            phone: '+5511999999999',
            document: 'DOC-' . uniqid(),
            loyaltyTier: $tier,
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );

        $this->guestRepo->save($profile);

        return (string) $profile->uuid;
    }

    #[Test]
    public function itFindsGuestInfoByUuid(): void
    {
        $uuid = $this->seedGuest('alice@hotel.com');

        $info = $this->gateway->findByUuid($uuid);

        $this->assertNotNull($info);
        $this->assertSame($uuid, $info->guestProfileId);
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
