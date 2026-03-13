<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

use DateTimeImmutable;
use Modules\Inventory\Domain\Exception\InvalidRoomStateException;
use Modules\Inventory\Domain\ValueObject\RoomStatus;
use Modules\Inventory\Domain\ValueObject\RoomType;
use Modules\Shared\Domain\AggregateRoot;
use Modules\Shared\Domain\Identity;

final class Room extends AggregateRoot
{
    private function __construct(
        public readonly RoomId $uuid,
        public readonly string $number,
        public readonly RoomType $type,
        public readonly int $floor,
        public readonly int $capacity,
        private(set) float $pricePerNight,
        private(set) RoomStatus $status,
        /** @var string[] */
        private(set) array $amenities,
        public readonly DateTimeImmutable $createdAt,
        private(set) ?DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param string[] $amenities
     */
    public static function create(
        RoomId $uuid,
        string $number,
        RoomType $type,
        int $floor,
        int $capacity,
        float $pricePerNight,
        array $amenities,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            uuid: $uuid,
            number: $number,
            type: $type,
            floor: $floor,
            capacity: $capacity,
            pricePerNight: $pricePerNight,
            status: RoomStatus::AVAILABLE,
            amenities: $amenities,
            createdAt: $createdAt,
            updatedAt: null,
        );
    }

    public function id(): Identity
    {
        return $this->uuid;
    }

    public function occupy(): void
    {
        if ($this->status !== RoomStatus::AVAILABLE) {
            throw InvalidRoomStateException::forTransition($this->status, RoomStatus::OCCUPIED);
        }

        $this->status = RoomStatus::OCCUPIED;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function release(): void
    {
        if ($this->status !== RoomStatus::OCCUPIED) {
            throw InvalidRoomStateException::forTransition($this->status, RoomStatus::AVAILABLE);
        }

        $this->status = RoomStatus::AVAILABLE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markMaintenance(): void
    {
        if ($this->status === RoomStatus::OCCUPIED) {
            throw InvalidRoomStateException::forTransition($this->status, RoomStatus::MAINTENANCE);
        }

        $this->status = RoomStatus::MAINTENANCE;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markOutOfOrder(): void
    {
        if ($this->status === RoomStatus::OCCUPIED) {
            throw InvalidRoomStateException::forTransition($this->status, RoomStatus::OUT_OF_ORDER);
        }

        $this->status = RoomStatus::OUT_OF_ORDER;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAvailable(): void
    {
        if ($this->status === RoomStatus::OCCUPIED) {
            throw InvalidRoomStateException::forTransition($this->status, RoomStatus::AVAILABLE);
        }

        $this->status = RoomStatus::AVAILABLE;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @param string[] $amenities
     */
    public function updateDetails(
        ?float $pricePerNight = null,
        ?array $amenities = null,
    ): void {
        if ($pricePerNight !== null) {
            $this->pricePerNight = $pricePerNight;
        }

        if ($amenities !== null) {
            $this->amenities = $amenities;
        }

        $this->updatedAt = new DateTimeImmutable();
    }
}
