<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\Service\GuestGateway;

/** @mixin Reservation */
final class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->resource;

        $guestGateway = app(GuestGateway::class);
        $guestInfo = $guestGateway->findByUuid($reservation->guestProfileId());

        $guest = $guestInfo
            ? [
                'guest_profile_id' => $guestInfo->guestProfileId,
                'full_name' => $guestInfo->fullName,
                'email' => $guestInfo->email,
                'phone' => $guestInfo->phone,
                'document' => $guestInfo->document,
                'is_vip' => $guestInfo->isVip,
            ]
            : [
                'guest_profile_id' => $reservation->guestProfileId(),
            ];

        return [
            'id' => (string) $reservation->uuid(),
            'status' => $reservation->status()->value,
            'guest' => $guest,
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
