<?php

declare(strict_types=1);

namespace Tests\Integration\Inventory;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\ValueObject\RoomType;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class EloquentRoomRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RoomRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $account = AccountModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'name' => 'Test Hotel',
            'created_at' => now(),
        ]);
        $this->app->make(TenantContext::class)->set($account->id);

        $this->repository = $this->app->make(RoomRepository::class);
    }

    private function createRoom(string $number = '101', string $type = 'DOUBLE'): Room
    {
        $room = Room::create(
            uuid: $this->repository->nextIdentity(),
            number: $number,
            type: RoomType::from($type),
            floor: (int) substr($number, 0, 1),
            capacity: $type === 'SUITE' ? 4 : ($type === 'DOUBLE' ? 2 : 1),
            pricePerNight: 250.00,
            amenities: ['wifi', 'tv'],
            createdAt: new DateTimeImmutable,
        );

        $this->repository->save($room);

        return $room;
    }

    #[Test]
    public function it_saves_and_finds_by_id(): void
    {
        $room = $this->createRoom();
        $found = $this->repository->findByUuid($room->uuid);

        $this->assertNotNull($found);
        $this->assertSame($room->number, $found->number);
        $this->assertSame($room->type, $found->type);
        $this->assertSame($room->floor, $found->floor);
    }

    #[Test]
    public function it_finds_by_number(): void
    {
        $this->createRoom('305');
        $found = $this->repository->findByNumber('305');

        $this->assertNotNull($found);
        $this->assertSame('305', $found->number);
    }

    #[Test]
    public function it_returns_null_for_unknown_number(): void
    {
        $this->assertNull($this->repository->findByNumber('999'));
    }

    #[Test]
    public function it_lists_with_pagination(): void
    {
        $this->createRoom('101');
        $this->createRoom('102');
        $this->createRoom('103');

        $result = $this->repository->list(page: 1, perPage: 2);

        $this->assertCount(2, $result->items);
        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->lastPage);
    }

    #[Test]
    public function it_filters_list_by_status(): void
    {
        $r1 = $this->createRoom('101');
        $r1->markMaintenance();
        $this->repository->save($r1);

        $this->createRoom('102');

        $result = $this->repository->list(status: 'maintenance');

        $this->assertCount(1, $result->items);
        $this->assertSame('101', $result->items[0]->number);
    }

    #[Test]
    public function it_filters_list_by_type(): void
    {
        $this->createRoom('101', 'SINGLE');
        $this->createRoom('201', 'DOUBLE');
        $this->createRoom('301', 'SUITE');

        $result = $this->repository->list(type: 'SUITE');

        $this->assertCount(1, $result->items);
        $this->assertSame(RoomType::SUITE, $result->items[0]->type);
    }

    #[Test]
    public function it_filters_list_by_floor(): void
    {
        $this->createRoom('101');
        $this->createRoom('201');
        $this->createRoom('202');

        $result = $this->repository->list(floor: 2);

        $this->assertCount(2, $result->items);
    }

    #[Test]
    public function it_removes_a_room(): void
    {
        $room = $this->createRoom();
        $this->repository->remove($room);

        $this->assertNull($this->repository->findByUuid($room->uuid));
    }

    #[Test]
    public function it_persists_status_changes(): void
    {
        $room = $this->createRoom();
        $room->occupy();
        $this->repository->save($room);

        $found = $this->repository->findByUuid($room->uuid);
        $this->assertSame('occupied', $found->status->value);
    }

    #[Test]
    public function count_returns_total(): void
    {
        $this->createRoom('101');
        $this->createRoom('102');

        $this->assertSame(2, $this->repository->count());
    }

    #[Test]
    public function count_by_status_groups_correctly(): void
    {
        $this->createRoom('101');

        $r2 = $this->createRoom('102');
        $r2->markMaintenance();
        $this->repository->save($r2);

        $result = $this->repository->countByStatus();

        $this->assertSame(1, $result['available']);
        $this->assertSame(1, $result['maintenance']);
    }

    #[Test]
    public function count_by_type_groups_correctly(): void
    {
        $this->createRoom('101', 'SINGLE');
        $this->createRoom('201', 'DOUBLE');
        $this->createRoom('202', 'DOUBLE');

        $result = $this->repository->countByType();

        $this->assertSame(1, $result['SINGLE']);
        $this->assertSame(2, $result['DOUBLE']);
    }

    #[Test]
    public function count_available_by_type_filters_correctly(): void
    {
        $this->createRoom('101', 'DOUBLE');
        $this->createRoom('102', 'DOUBLE');

        $r3 = $this->createRoom('103', 'DOUBLE');
        $r3->markMaintenance();
        $this->repository->save($r3);

        $this->assertSame(2, $this->repository->countAvailableByType('DOUBLE'));
        $this->assertSame(0, $this->repository->countAvailableByType('SUITE'));
    }
}
