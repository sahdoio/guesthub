<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\Room;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Domain\ValueObject\RoomStatus;
use Modules\Inventory\Domain\ValueObject\RoomType;
use Modules\Inventory\Infrastructure\Persistence\RoomReflector;
use Modules\Shared\Domain\PaginatedResult;

final readonly class EloquentRoomRepository implements RoomRepository
{
    public function __construct(
        private RoomModel $model,
    ) {}

    public function save(Room $room): void
    {
        $data = $this->toRecord($room);

        $this->model->newQuery()
            ->updateOrInsert(['uuid' => $data['uuid']], $data);
    }

    public function findByUuid(RoomId $uuid): ?Room
    {
        $record = $this->model->newQuery()
            ->where('uuid', $uuid->value)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function findByNumber(string $number): ?Room
    {
        $record = $this->model->newQuery()
            ->where('number', $number)
            ->first();

        return $record ? $this->toEntity($record) : null;
    }

    public function list(
        int $page = 1,
        int $perPage = 15,
        ?string $status = null,
        ?string $type = null,
        ?int $floor = null,
    ): PaginatedResult {
        $query = $this->model->newQuery()->orderBy('floor')->orderBy('number');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($floor !== null) {
            $query->where('floor', $floor);
        }

        $paginator = $query->paginate(perPage: $perPage, page: $page);

        $items = collect($paginator->items())
            ->map(fn(object $record) => $this->toEntity($record))
            ->all();

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    public function remove(Room $room): void
    {
        $this->model->newQuery()
            ->where('uuid', $room->uuid->value)
            ->delete();
    }

    public function nextIdentity(): RoomId
    {
        return RoomId::generate();
    }

    public function count(): int
    {
        return (int) $this->model->newQuery()->count();
    }

    public function countByStatus(): array
    {
        return $this->model->newQuery()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function countByType(): array
    {
        return $this->model->newQuery()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();
    }

    public function countAvailableByType(string $type): int
    {
        return (int) $this->model->newQuery()
            ->where('type', $type)
            ->where('status', RoomStatus::AVAILABLE->value)
            ->count();
    }

    private function toEntity(object $record): Room
    {
        return RoomReflector::reconstruct(
            uuid: RoomId::fromString($record->uuid),
            number: $record->number,
            type: RoomType::from($record->type),
            floor: (int) $record->floor,
            capacity: (int) $record->capacity,
            pricePerNight: (float) $record->price_per_night,
            status: RoomStatus::from($record->status),
            amenities: is_array($record->amenities) ? $record->amenities : json_decode($record->amenities ?? '[]', true),
            createdAt: new DateTimeImmutable($record->created_at),
            updatedAt: $record->updated_at ? new DateTimeImmutable($record->updated_at) : null,
        );
    }

    private function toRecord(Room $room): array
    {
        return [
            'uuid' => $room->uuid->value,
            'number' => $room->number,
            'type' => $room->type->value,
            'floor' => $room->floor,
            'capacity' => $room->capacity,
            'price_per_night' => $room->pricePerNight,
            'status' => $room->status->value,
            'amenities' => json_encode($room->amenities),
            'created_at' => $room->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $room->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
