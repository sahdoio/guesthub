<?php

declare(strict_types=1);

namespace Tests\Integration\Reservation;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Domain\User;
use Modules\User\Domain\Repository\UserRepository;
use Modules\User\Domain\ValueObject\LoyaltyTier;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\HotelModel;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class ReservationStatsTest extends TestCase
{
    use RefreshDatabase;

    private ReservationRepository $repository;

    private string $guestId;

    private string $accountUuid;

    private string $hotelUuid;

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
        $this->app->make(TenantContext::class)->set($account->id);

        $this->hotelUuid = Uuid::uuid7()->toString();
        HotelModel::withoutGlobalScopes()->create([
            'uuid' => $this->hotelUuid,
            'account_id' => $account->id,
            'name' => 'Test Hotel',
            'slug' => 'test-hotel',
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
        );
        $guestRepo->save($guest);
        $this->guestId = (string) $guest->uuid;
    }

    private function createReservation(string $roomType = 'DOUBLE'): Reservation
    {
        $reservation = Reservation::create(
            uuid: $this->repository->nextIdentity(),
            guestId: $this->guestId,
            accountId: $this->accountUuid,
            hotelId: $this->hotelUuid,
            period: new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+3 days'),
            ),
            roomType: $roomType,
        );

        $this->repository->save($reservation);

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
        $this->repository->save($r2);

        $r3 = $this->createReservation();
        $r3->cancel('testing');
        $this->repository->save($r3);

        $result = $this->repository->countByStatus();

        $this->assertSame(1, $result['pending']);
        $this->assertSame(1, $result['confirmed']);
        $this->assertSame(1, $result['cancelled']);
    }

    #[Test]
    public function count_by_room_type_groups_correctly(): void
    {
        $this->createReservation('SINGLE');
        $this->createReservation('DOUBLE');
        $this->createReservation('DOUBLE');
        $this->createReservation('SUITE');

        $result = $this->repository->countByRoomType();

        $this->assertSame(1, $result['SINGLE']);
        $this->assertSame(2, $result['DOUBLE']);
        $this->assertSame(1, $result['SUITE']);
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
        $r1->checkIn('101');
        $this->repository->save($r1);

        $r2 = $this->createReservation();
        $r2->confirm();
        $r2->checkIn('102');
        $this->repository->save($r2);

        $this->assertSame(2, $this->repository->countTodayCheckIns());
    }

    #[Test]
    public function count_today_check_outs_counts_correctly(): void
    {
        $r = $this->createReservation();
        $r->confirm();
        $r->checkIn('101');
        $r->checkOut();
        $this->repository->save($r);

        $this->assertSame(1, $this->repository->countTodayCheckOuts());
    }

    #[Test]
    public function count_by_status_returns_empty_when_no_reservations(): void
    {
        $this->assertSame([], $this->repository->countByStatus());
    }

    #[Test]
    public function count_by_room_type_returns_empty_when_no_reservations(): void
    {
        $this->assertSame([], $this->repository->countByRoomType());
    }
}
