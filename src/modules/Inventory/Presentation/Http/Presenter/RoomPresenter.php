<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Presenter;

use Modules\Inventory\Domain\Room;

final class RoomPresenter
{
    public static function fromDomain(Room $room): array
    {
        return [
            'id' => (string) $room->uuid,
            'number' => $room->number,
            'type' => $room->type->value,
            'floor' => $room->floor,
            'capacity' => $room->capacity,
            'price_per_night' => $room->pricePerNight,
            'status' => $room->status->value,
            'amenities' => $room->amenities,
            'created_at' => $room->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $room->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
