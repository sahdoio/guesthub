<?php

declare(strict_types=1);

namespace Tests\Integration\Reservation;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;
use Modules\Reservation\Domain\ValueObject\ReservationStatus;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Infrastructure\Persistence\Eloquent\EloquentReservationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(EloquentReservationRepository::class)]
final class EloquentReservationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReservationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(ReservationRepository::class);
    }

    private function createReservation(array $overrides = []): Reservation
    {
        return Reservation::create(
            $overrides['uuid'] ?? $this->repository->nextIdentity(),
            $overrides['guestProfileId'] ?? 'guest-uuid-123',
            $overrides['period'] ?? new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+4 days'),
            ),
            $overrides['roomType'] ?? 'DOUBLE',
        );
    }

    #[Test]
    public function itSavesAndFindsByUuid(): void
    {
        $reservation = $this->createReservation();
        $this->repository->save($reservation);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertNotNull($found);
        $this->assertTrue($reservation->uuid->equals($found->uuid));
        $this->assertSame('guest-uuid-123', $found->guestProfileId);
        $this->assertSame('DOUBLE', $found->roomType);
        $this->assertSame(ReservationStatus::PENDING, $found->status);
    }

    #[Test]
    public function itReturnsNullForUnknownUuid(): void
    {
        $this->assertNull($this->repository->findByUuid(ReservationId::generate()));
    }

    #[Test]
    public function itPersistsStatusChanges(): void
    {
        $reservation = $this->createReservation();
        $this->repository->save($reservation);

        $reservation->confirm();
        $this->repository->save($reservation);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertSame(ReservationStatus::CONFIRMED, $found->status);
        $this->assertNotNull($found->confirmedAt);
    }

    #[Test]
    public function itPersistsSpecialRequests(): void
    {
        $reservation = $this->createReservation();
        $reservation->addSpecialRequest(RequestType::EARLY_CHECK_IN, 'Early arrival');
        $reservation->addSpecialRequest(RequestType::EXTRA_BED, 'For child');
        $this->repository->save($reservation);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertCount(2, $found->specialRequests);
        $this->assertSame('Early arrival', $found->specialRequests[0]->description);
        $this->assertSame('For child', $found->specialRequests[1]->description);
    }

    #[Test]
    public function itPersistsCancellation(): void
    {
        $reservation = $this->createReservation();
        $this->repository->save($reservation);

        $reservation->cancel('Plans changed');
        $this->repository->save($reservation);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertSame(ReservationStatus::CANCELLED, $found->status);
        $this->assertSame('Plans changed', $found->cancellationReason);
        $this->assertNotNull($found->cancelledAt);
    }

    #[Test]
    public function itListsReservations(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->repository->save($this->createReservation(['guestProfileId' => "guest-{$i}"]));
        }

        $result = $this->repository->list(1, 2);

        $this->assertSame(3, $result->total);
        $this->assertCount(2, $result->items);
        $this->assertSame(2, $result->lastPage);
    }

    #[Test]
    public function itListsFilteredByStatus(): void
    {
        $pending = $this->createReservation();
        $this->repository->save($pending);

        $confirmed = $this->createReservation(['guestProfileId' => 'guest-confirmed']);
        $confirmed->confirm();
        $this->repository->save($confirmed);

        $result = $this->repository->list(1, 15, status: 'confirmed');

        $this->assertSame(1, $result->total);
        $this->assertSame(ReservationStatus::CONFIRMED, $result->items[0]->status);
    }

    #[Test]
    public function itListsFilteredByRoomType(): void
    {
        $this->repository->save($this->createReservation(['roomType' => 'DOUBLE']));
        $this->repository->save($this->createReservation(['roomType' => 'SUITE']));
        $this->repository->save($this->createReservation(['roomType' => 'DOUBLE']));

        $result = $this->repository->list(1, 15, roomType: 'SUITE');

        $this->assertSame(1, $result->total);
        $this->assertSame('SUITE', $result->items[0]->roomType);
    }

    #[Test]
    public function itGeneratesUniqueIdentities(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        $this->assertFalse($id1->equals($id2));
    }
}
