<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Query;

use JsonSerializable;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\SpecialRequest;

final readonly class ReservationReadModel implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $status,
        public array $guest,
        public array $stay,
        public string $checkIn,
        public string $checkOut,
        public int $nights,
        public int $adults,
        public int $children,
        public int $babies,
        public int $pets,
        public array $specialRequests,
        public string $createdAt,
        public ?string $confirmedAt,
        public ?string $checkedInAt,
        public ?string $checkedOutAt,
        public ?string $cancelledAt,
        public ?string $freeCancellationUntil,
        public ?string $cancellationReason,
    ) {}

    public static function fromReservation(Reservation $reservation): self
    {
        return new self(
            id: (string) $reservation->uuid,
            status: $reservation->status->value,
            guest: ['guest_id' => $reservation->guestId],
            stay: ['stay_id' => $reservation->stayId],
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            nights: $reservation->period->nights(),
            adults: $reservation->adults,
            children: $reservation->children,
            babies: $reservation->babies,
            pets: $reservation->pets,
            specialRequests: array_map(fn (SpecialRequest $sr) => [
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
            freeCancellationUntil: $reservation->freeCancellationUntil?->format('Y-m-d H:i:s'),
            cancellationReason: $reservation->cancellationReason,
        );
    }

    public function withGuest(array $guest): self
    {
        return new self(
            id: $this->id,
            status: $this->status,
            guest: $guest,
            stay: $this->stay,
            checkIn: $this->checkIn,
            checkOut: $this->checkOut,
            nights: $this->nights,
            adults: $this->adults,
            children: $this->children,
            babies: $this->babies,
            pets: $this->pets,
            specialRequests: $this->specialRequests,
            createdAt: $this->createdAt,
            confirmedAt: $this->confirmedAt,
            checkedInAt: $this->checkedInAt,
            checkedOutAt: $this->checkedOutAt,
            cancelledAt: $this->cancelledAt,
            freeCancellationUntil: $this->freeCancellationUntil,
            cancellationReason: $this->cancellationReason,
        );
    }

    public function withStay(array $stay): self
    {
        return new self(
            id: $this->id,
            status: $this->status,
            guest: $this->guest,
            stay: $stay,
            checkIn: $this->checkIn,
            checkOut: $this->checkOut,
            nights: $this->nights,
            adults: $this->adults,
            children: $this->children,
            babies: $this->babies,
            pets: $this->pets,
            specialRequests: $this->specialRequests,
            createdAt: $this->createdAt,
            confirmedAt: $this->confirmedAt,
            checkedInAt: $this->checkedInAt,
            checkedOutAt: $this->checkedOutAt,
            cancelledAt: $this->cancelledAt,
            freeCancellationUntil: $this->freeCancellationUntil,
            cancellationReason: $this->cancellationReason,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'guest' => $this->guest,
            'stay' => $this->stay,
            'period' => [
                'check_in' => $this->checkIn,
                'check_out' => $this->checkOut,
                'nights' => $this->nights,
            ],
            'guests' => [
                'adults' => $this->adults,
                'children' => $this->children,
                'babies' => $this->babies,
                'pets' => $this->pets,
            ],
            'special_requests' => $this->specialRequests,
            'free_cancellation_until' => $this->freeCancellationUntil,
            'cancellation_reason' => $this->cancellationReason,
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
