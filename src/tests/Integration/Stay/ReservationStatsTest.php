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
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class ReservationStatsTest extends TestCase
{
    use RefreshDatabase;

    private ReservationRepository $repository;

    private string $guestId;

    private string $accountUuid;

    private int $accountNumericId;

    private string $stayUuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountUuid = Uuid::uuid7()->toString();
        $account = AccountModel::create([
            'uuid' => $this->accountUuid,
            'name' => 'Test Organization',
            'slug' => 'test-org',
            'status' => 'active',
            'created_at' => now(),
        ]);
        $this->accountNumericId = $account->id;
        $this->app->make(TenantContext::class)->set($this->accountUuid);

        $this->stayUuid = Uuid::uuid7()->toString();
        StayModel::withoutGlobalScopes()->create([
            'uuid' => $this->stayUuid,
            'account_uuid' => $this->accountUuid,
            'name' => 'Test Stay',
            'slug' => 'test-stay',
            'type' => 'room',
            'category' => 'hotel_room',
            'price_per_night' => 250.00,
            'capacity' => 2,
            'status' => 'active',
            'created_at' => now(),
        ]);

        $this->repository = $this->app->make(ReservationRepository::class);

        $guestRepo = $this->app->make(UserRepository::class);
        $guest = User::create(
            uuid: $guestRepo->nextIdentity(),
            fullName: 'Test Guest',
            email: 'test@hotel.com',
            phone: '5511999999999',
            document: 'DOC123',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable,
            hashedPassword: 'hashed_default',
            actorType: 'guest',
            emailUniquenessChecker: $this->app->make(\Modules\IAM\Domain\Service\UserEmailUniquenessChecker::class),
        );
        $guestRepo->save($guest);
        $this->guestId = (string) $guest->uuid;
    }

    private function createReservation(): Reservation
    {
        $reservation = Reservation::create(
            uuid: $this->repository->nextIdentity(),
            guestId: $this->guestId,
            accountId: $this->accountUuid,
            stayId: $this->stayUuid,
            period: new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+3 days'),
            ),
        );

        $this->repository->save($reservation, $this->accountNumericId);

        return $reservation;
    }

    #[Test]
    public function count_returns_zero_with_no_reservations(): void
    {
        $this->assertSame(0, $this->repository->count());
    }

    #[Test]
    public function count_returns_total_reservations(): void
    {
        $this->createReservation();
        $this->createReservation();

        $this->assertSame(2, $this->repository->count());
    }

    #[Test]
    public function count_by_status_groups_correctly(): void
    {
        $this->createReservation();

        $r2 = $this->createReservation();
        $r2->confirm();
        $this->repository->save($r2, $this->accountNumericId);

        $r3 = $this->createReservation();
        $r3->cancel('testing');
        $this->repository->save($r3, $this->accountNumericId);

        $result = $this->repository->countByStatus();

        $this->assertSame(1, $result['pending']);
        $this->assertSame(1, $result['confirmed']);
        $this->assertSame(1, $result['cancelled']);
    }

    #[Test]
    public function count_today_check_ins_returns_zero_by_default(): void
    {
        $this->createReservation();

        $this->assertSame(0, $this->repository->countTodayCheckIns());
    }

    #[Test]
    public function count_today_check_ins_counts_correctly(): void
    {
        $r1 = $this->createReservation();
        $r1->confirm();
        $r1->checkIn();
        $this->repository->save($r1, $this->accountNumericId);

        $r2 = $this->createReservation();
        $r2->confirm();
        $r2->checkIn();
        $this->repository->save($r2, $this->accountNumericId);

        $this->assertSame(2, $this->repository->countTodayCheckIns());
    }

    #[Test]
    public function count_today_check_outs_counts_correctly(): void
    {
        $r = $this->createReservation();
        $r->confirm();
        $r->checkIn();
        $r->checkOut();
        $this->repository->save($r, $this->accountNumericId);

        $this->assertSame(1, $this->repository->countTodayCheckOuts());
    }

    #[Test]
    public function count_by_status_returns_empty_when_no_reservations(): void
    {
        $this->assertSame([], $this->repository->countByStatus());
    }
}
