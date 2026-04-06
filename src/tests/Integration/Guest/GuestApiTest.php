<?php

declare(strict_types=1);

namespace Tests\Integration\Guest;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Service\UserEmailUniquenessChecker;
use Modules\IAM\Domain\User;
use Modules\IAM\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Integration\Dto\UserData;
use Modules\IAM\Infrastructure\Integration\UserApi;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

#[CoversClass(UserApi::class)]
final class GuestApiTest extends TestCase
{
    use RefreshDatabase;

    private UserApi $api;

    private UserRepository $repository;

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
        $this->app->make(TenantContext::class)->set($account->uuid);

        $this->api = $this->app->make(UserApi::class);
        $this->repository = $this->app->make(UserRepository::class);
    }

    private function createUser(string $name = 'Alice Johnson', string $email = 'alice@hotel.com'): User
    {
        $id = $this->repository->nextIdentity();
        $user = User::create(
            uuid: $id,
            fullName: $name,
            email: $email,
            phone: '5511999999999',
            document: 'ABC123',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable,
            hashedPassword: 'hashed_default',
            actorType: 'guest',
            emailUniquenessChecker: $this->app->make(UserEmailUniquenessChecker::class),
        );
        $this->repository->save($user);

        return $user;
    }

    #[Test]
    public function itFindsGuestProfileByUuid(): void
    {
        $user = $this->createUser();
        $uuid = (string) $user->uuid;

        $data = $this->api->findByUuid($uuid);

        $this->assertInstanceOf(UserData::class, $data);
        $this->assertSame($uuid, $data->uuid);
        $this->assertSame('Alice Johnson', $data->fullName);
        $this->assertSame('alice@hotel.com', $data->email);
        $this->assertSame('5511999999999', $data->phone);
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
