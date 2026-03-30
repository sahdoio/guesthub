<?php

declare(strict_types=1);

namespace Tests\Integration\Stay;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\RequestType;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;
use Modules\Stay\Domain\ValueObject\ReservationStatus;
use Modules\Stay\Infrastructure\Persistence\Eloquent\EloquentReservationRepository;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

#[CoversClass(EloquentReservationRepository::class)]
final class EloquentReservationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ReservationRepository $repository;

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
    }

    private function createReservation(array $overrides = []): Reservation
    {
        return Reservation::create(
            $overrides['uuid'] ?? $this->repository->nextIdentity(),
            $overrides['guestId'] ?? 'guest-uuid-123',
            $overrides['accountId'] ?? $this->accountUuid,
            $overrides['stayId'] ?? $this->stayUuid,
            $overrides['period'] ?? new ReservationPeriod(
                new DateTimeImmutable('+1 day'),
                new DateTimeImmutable('+4 days'),
            ),
        );
    }

    #[Test]
    public function it_saves_and_finds_by_uuid(): void
    {
        $reservation = $this->createReservation();
        $this->repository->save($reservation, $this->accountNumericId);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertNotNull($found);
        $this->assertTrue($reservation->uuid->equals($found->uuid));
        $this->assertSame('guest-uuid-123', $found->guestId);
        $this->assertSame(ReservationStatus::PENDING, $found->status);
    }

    #[Test]
    public function it_returns_null_for_unknown_uuid(): void
    {
        $this->assertNull($this->repository->findByUuid(ReservationId::generate()));
    }

    #[Test]
    public function it_persists_status_changes(): void
    {
        $reservation = $this->createReservation();
        $this->repository->save($reservation, $this->accountNumericId);

        $reservation->confirm();
        $this->repository->save($reservation, $this->accountNumericId);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertSame(ReservationStatus::CONFIRMED, $found->status);
        $this->assertNotNull($found->confirmedAt);
    }

    #[Test]
    public function it_persists_special_requests(): void
    {
        $reservation = $this->createReservation();
        $reservation->addSpecialRequest(RequestType::EARLY_CHECK_IN, 'Early arrival');
        $reservation->addSpecialRequest(RequestType::EXTRA_BED, 'For child');
        $this->repository->save($reservation, $this->accountNumericId);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertCount(2, $found->specialRequests);
        $this->assertSame('Early arrival', $found->specialRequests[0]->description);
        $this->assertSame('For child', $found->specialRequests[1]->description);
    }

    #[Test]
    public function it_persists_cancellation(): void
    {
        $reservation = $this->createReservation();
        $this->repository->save($reservation, $this->accountNumericId);

        $reservation->cancel('Plans changed');
        $this->repository->save($reservation, $this->accountNumericId);

        $found = $this->repository->findByUuid($reservation->uuid);

        $this->assertSame(ReservationStatus::CANCELLED, $found->status);
        $this->assertSame('Plans changed', $found->cancellationReason);
        $this->assertNotNull($found->cancelledAt);
    }

    #[Test]
    public function it_lists_reservations(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->repository->save($this->createReservation(['guestId' => "guest-{$i}"]), $this->accountNumericId);
        }

        $result = $this->repository->list(1, 2);

        $this->assertSame(3, $result->total);
        $this->assertCount(2, $result->items);
        $this->assertSame(2, $result->lastPage);
    }

    #[Test]
    public function it_lists_filtered_by_status(): void
    {
        $pending = $this->createReservation();
        $this->repository->save($pending, $this->accountNumericId);

        $confirmed = $this->createReservation(['guestId' => 'guest-confirmed']);
        $confirmed->confirm();
        $this->repository->save($confirmed, $this->accountNumericId);

        $result = $this->repository->list(1, 15, status: 'confirmed');

        $this->assertSame(1, $result->total);
        $this->assertSame(ReservationStatus::CONFIRMED, $result->items[0]->status);
    }

    #[Test]
    public function it_generates_unique_identities(): void
    {
        $id1 = $this->repository->nextIdentity();
        $id2 = $this->repository->nextIdentity();

        $this->assertFalse($id1->equals($id2));
    }
}
