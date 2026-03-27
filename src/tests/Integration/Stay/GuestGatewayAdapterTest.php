<?php

declare(strict_types=1);

namespace Tests\Integration\Stay;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Infrastructure\Integration\GuestGatewayAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

#[CoversClass(GuestGatewayAdapter::class)]
final class GuestGatewayAdapterTest extends TestCase
{
    use RefreshDatabase;

    private GuestGateway $gateway;

    private UserRepository $userRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Organization',
            'slug' => 'test-org',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $this->app->make(TenantContext::class)->set($account->id);

        $this->gateway = $this->app->make(GuestGateway::class);
        $this->userRepo = $this->app->make(UserRepository::class);
    }

    private function seedGuest(string $email, LoyaltyTier $tier = LoyaltyTier::BRONZE): string
    {
        $user = User::create(
            uuid: $this->userRepo->nextIdentity(),
            fullName: 'Test Guest',
            email: $email,
            phone: '5511999999999',
            document: 'DOC-'.uniqid(),
            loyaltyTier: $tier,
            preferences: [],
            createdAt: new DateTimeImmutable,
            hashedPassword: 'hashed_default',
            actorType: 'guest',
            emailUniquenessChecker: $this->app->make(\Modules\IAM\Domain\Service\UserEmailUniquenessChecker::class),
        );

        $this->userRepo->save($user);

        return (string) $user->uuid;
    }

    #[Test]
    public function it_finds_guest_info_by_uuid(): void
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
    public function it_returns_null_for_unknown_uuid(): void
    {
        $this->assertNull($this->gateway->findByUuid('00000000-0000-0000-0000-000000000000'));
    }

    #[Test]
    public function it_identifies_gold_guest_as_vip(): void
    {
        $uuid = $this->seedGuest('gold@hotel.com', LoyaltyTier::GOLD);

        $info = $this->gateway->findByUuid($uuid);

        $this->assertTrue($info->isVip);
    }

    #[Test]
    public function it_identifies_platinum_guest_as_vip(): void
    {
        $uuid = $this->seedGuest('platinum@hotel.com', LoyaltyTier::PLATINUM);

        $info = $this->gateway->findByUuid($uuid);

        $this->assertTrue($info->isVip);
    }

    #[Test]
    public function it_identifies_silver_guest_as_non_vip(): void
    {
        $uuid = $this->seedGuest('silver@hotel.com', LoyaltyTier::SILVER);

        $info = $this->gateway->findByUuid($uuid);

        $this->assertFalse($info->isVip);
    }
}
