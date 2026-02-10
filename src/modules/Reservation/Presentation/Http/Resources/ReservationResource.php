<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Reservation\Domain\Reservation;

/** @mixin Reservation */
final class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->resource;

        return [
            'id' => (string) $reservation->reservationId(),
            'status' => $reservation->status()->value,
            'guest' => [
                'full_name' => $reservation->guest()->fullName,
                'email' => (string) $reservation->guest()->email,
                'phone' => (string) $reservation->guest()->phone,
                'document' => $reservation->guest()->document,
                'is_vip' => $reservation->guest()->isVip,
            ],
            'period' => [
                'check_in' => $reservation->period()->checkIn->format('Y-m-d'),
                'check_out' => $reservation->period()->checkOut->format('Y-m-d'),
                'nights' => $reservation->period()->nights(),
            ],
            'room_type' => $reservation->roomType(),
            'assigned_room_number' => $reservation->assignedRoomNumber(),
            'special_requests' => array_map(fn($sr) => [
                'id' => (string) $sr->id(),
                'type' => $sr->type()->value,
                'description' => $sr->description(),
                'status' => $sr->status()->value,
                'fulfilled_at' => $sr->fulfilledAt()?->format('Y-m-d H:i:s'),
                'created_at' => $sr->createdAt()->format('Y-m-d H:i:s'),
            ], $reservation->specialRequests()),
            'timestamps' => [
                'created_at' => $reservation->createdAt()->format('Y-m-d H:i:s'),
                'confirmed_at' => $reservation->confirmedAt()?->format('Y-m-d H:i:s'),
                'checked_in_at' => $reservation->checkedInAt()?->format('Y-m-d H:i:s'),
                'checked_out_at' => $reservation->checkedOutAt()?->format('Y-m-d H:i:s'),
                'cancelled_at' => $reservation->cancelledAt()?->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
