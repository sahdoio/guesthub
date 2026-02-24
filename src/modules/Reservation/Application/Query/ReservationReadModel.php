<?php

declare(strict_types=1);

namespace Modules\Reservation\Application\Query;

use JsonSerializable;
use Modules\Reservation\Domain\Entity\SpecialRequest;
use Modules\Reservation\Domain\Reservation;

final readonly class ReservationReadModel implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $status,
        public array $guest,
        public string $checkIn,
        public string $checkOut,
        public int $nights,
        public string $roomType,
        public ?string $assignedRoomNumber,
        public array $specialRequests,
        public string $createdAt,
        public ?string $confirmedAt,
        public ?string $checkedInAt,
        public ?string $checkedOutAt,
        public ?string $cancelledAt,
    ) {}

    public static function fromReservation(Reservation $reservation): self
    {
        return new self(
            id: (string) $reservation->uuid,
            status: $reservation->status->value,
            guest: ['guest_profile_id' => $reservation->guestProfileId],
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            nights: $reservation->period->nights(),
            roomType: $reservation->roomType,
            assignedRoomNumber: $reservation->assignedRoomNumber,
            specialRequests: array_map(fn(SpecialRequest $sr) => [
                'id' => (string) $sr->id,
                'type' => $sr->type->value,
                'description' => $sr->description,
                'status' => $sr->status->value,
                'fulfilled_at' => $sr->fulfilledAt?->format('Y-m-d H:i:s'),
                'created_at' => $sr->createdAt->format('Y-m-d H:i:s'),
            ], $reservation->specialRequests),
            createdAt: $reservation->createdAt->format('Y-m-d H:i:s'),
            confirmedAt: $reservation->confirmedAt?->format('Y-m-d H:i:s'),
            checkedInAt: $reservation->checkedInAt?->format('Y-m-d H:i:s'),
            checkedOutAt: $reservation->checkedOutAt?->format('Y-m-d H:i:s'),
            cancelledAt: $reservation->cancelledAt?->format('Y-m-d H:i:s'),
        );
    }

    public function withGuest(array $guest): self
    {
        return new self(
            id: $this->id,
            status: $this->status,
            guest: $guest,
            checkIn: $this->checkIn,
            checkOut: $this->checkOut,
            nights: $this->nights,
            roomType: $this->roomType,
            assignedRoomNumber: $this->assignedRoomNumber,
            specialRequests: $this->specialRequests,
            createdAt: $this->createdAt,
            confirmedAt: $this->confirmedAt,
            checkedInAt: $this->checkedInAt,
            checkedOutAt: $this->checkedOutAt,
            cancelledAt: $this->cancelledAt,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'guest' => $this->guest,
            'period' => [
                'check_in' => $this->checkIn,
                'check_out' => $this->checkOut,
                'nights' => $this->nights,
            ],
            'room_type' => $this->roomType,
            'assigned_room_number' => $this->assignedRoomNumber,
            'special_requests' => $this->specialRequests,
            'timestamps' => [
                'created_at' => $this->createdAt,
                'confirmed_at' => $this->confirmedAt,
                'checked_in_at' => $this->checkedInAt,
                'checked_out_at' => $this->checkedOutAt,
                'cancelled_at' => $this->cancelledAt,
            ],
        ];
    }
}
