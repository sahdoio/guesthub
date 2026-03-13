<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\ValueObject\RoomType;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

final class RoomCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(ActorModel::create([
            'uuid' => Uuid::uuid7()->toString(),
            'type' => 'system',
            'name' => 'Test System',
            'email' => 'system@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
        ]));
    }

    private function createRoom(array $overrides = []): string
    {
        $repository = $this->app->make(RoomRepository::class);

        $room = Room::create(
            uuid: $repository->nextIdentity(),
            number: $overrides['number'] ?? '101',
            type: RoomType::from($overrides['type'] ?? 'DOUBLE'),
            floor: $overrides['floor'] ?? 1,
            capacity: $overrides['capacity'] ?? 2,
            pricePerNight: $overrides['price_per_night'] ?? 250.00,
            amenities: $overrides['amenities'] ?? ['wifi', 'tv'],
            createdAt: new DateTimeImmutable(),
        );

        $repository->save($room);

        return (string) $room->uuid;
    }

    // --- List ---

    #[Test]
    public function itListsRoomsWithPagination(): void
    {
        $this->createRoom(['number' => '101']);
        $this->createRoom(['number' => '102']);
        $this->createRoom(['number' => '103']);

        $response = $this->getJson('/api/rooms?page=1&per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2);
    }

    #[Test]
    public function itFiltersRoomsByStatus(): void
    {
        $id = $this->createRoom(['number' => '101']);
        $this->createRoom(['number' => '102']);

        // Change status via domain
        $repo = $this->app->make(RoomRepository::class);
        $room = $repo->findByUuid(\Modules\Inventory\Domain\RoomId::fromString($id));
        $room->markMaintenance();
        $repo->save($room);

        $response = $this->getJson('/api/rooms?status=maintenance');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'maintenance');
    }

    #[Test]
    public function itFiltersRoomsByType(): void
    {
        $this->createRoom(['number' => '101', 'type' => 'SINGLE']);
        $this->createRoom(['number' => '201', 'type' => 'SUITE']);

        $response = $this->getJson('/api/rooms?type=SUITE');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'SUITE');
    }

    // --- Show ---

    #[Test]
    public function itShowsARoom(): void
    {
        $id = $this->createRoom();

        $response = $this->getJson("/api/rooms/{$id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.number', '101')
            ->assertJsonPath('data.type', 'DOUBLE')
            ->assertJsonStructure([
                'data' => ['id', 'number', 'type', 'floor', 'capacity', 'price_per_night', 'status', 'amenities', 'created_at'],
            ]);
    }

    // --- Update ---

    #[Test]
    public function itUpdatesPriceAndAmenities(): void
    {
        $id = $this->createRoom();

        $response = $this->putJson("/api/rooms/{$id}", [
            'price_per_night' => 350.00,
            'amenities' => ['wifi', 'tv', 'minibar', 'safe'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.price_per_night', 350)
            ->assertJsonPath('data.amenities', ['wifi', 'tv', 'minibar', 'safe']);
    }

    // --- Change Status ---

    #[Test]
    public function itChangesRoomStatusToMaintenance(): void
    {
        $id = $this->createRoom();

        $response = $this->patchJson("/api/rooms/{$id}/status", [
            'status' => 'maintenance',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'maintenance');
    }

    #[Test]
    public function itRejectsInvalidStatus(): void
    {
        $id = $this->createRoom();

        $this->patchJson("/api/rooms/{$id}/status", ['status' => 'invalid'])
            ->assertStatus(422);
    }

    // --- Delete ---

    #[Test]
    public function itDeletesARoom(): void
    {
        $id = $this->createRoom();

        $this->deleteJson("/api/rooms/{$id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('rooms', ['uuid' => $id]);
    }

    #[Test]
    public function itReturnsEmptyListWhenNoRooms(): void
    {
        $response = $this->getJson('/api/rooms');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }
}
