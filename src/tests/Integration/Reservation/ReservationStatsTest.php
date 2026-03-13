<?php

declare(strict_types=1);

namespace Tests\Integration\Reservation;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Guest\Domain\GuestProfile;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Domain\ValueObject\LoyaltyTier;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ReservationStatsTest extends TestCase
{
    use RefreshDatabase;

    private ReservationRepository $repository;
    private string $guestId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(ReservationRepository::class);

        $guestRepo = $this->app->make(GuestProfileRepository::class);
        $profile = GuestProfile::create(
            uuid: $guestRepo->nextIdentity(),
            fullName: 'Test Guest',
            email: 'test@hotel.com',
            phone: '+5511999999999',
            document: 'DOC123',
            loyaltyTier: LoyaltyTier::BRONZE,
            preferences: [],
            createdAt: new DateTimeImmutable(),
        );
        $guestRepo->save($profile);
        $this->guestId = (string) $profile->uuid;
    }

    private function createReservation(string $roomType = 'DOUBLE'): Reservation
    {
        $reservation = Reservation::create(
            uuid: $this->repository->nextIdentity(),
            guestProfileId: $this->guestId,
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
    public function countReturnsZeroWithNoReservations(): void
    {
        $this->assertSame(0, $this->repository->count());
    }

    #[Test]
    public function countReturnsTotalReservations(): void
    {
        $this->createReservation();
        $this->createReservation();

        $this->assertSame(2, $this->repository->count());
    }

    #[Test]
    public function countByStatusGroupsCorrectly(): void
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
    public function countByRoomTypeGroupsCorrectly(): void
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
    public function countTodayCheckInsReturnsZeroByDefault(): void
    {
        $this->createReservation();

        $this->assertSame(0, $this->repository->countTodayCheckIns());
    }

    #[Test]
    public function countTodayCheckInsCountsCorrectly(): void
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
    public function countTodayCheckOutsCountsCorrectly(): void
    {
        $r = $this->createReservation();
        $r->confirm();
        $r->checkIn('101');
        $r->checkOut();
        $this->repository->save($r);

        $this->assertSame(1, $this->repository->countTodayCheckOuts());
    }

    #[Test]
    public function countByStatusReturnsEmptyWhenNoReservations(): void
    {
        $this->assertSame([], $this->repository->countByStatus());
    }

    #[Test]
    public function countByRoomTypeReturnsEmptyWhenNoReservations(): void
    {
        $this->assertSame([], $this->repository->countByRoomType());
    }
}
