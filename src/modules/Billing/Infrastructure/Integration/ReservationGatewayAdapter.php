<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Integration;

use DateTimeImmutable;
use Modules\Billing\Domain\DTO\ReservationInfo;
use Modules\Billing\Domain\Service\ReservationGateway;
use Modules\Stay\Infrastructure\Persistence\Eloquent\ReservationModel;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

final class ReservationGatewayAdapter implements ReservationGateway
{
    public function findReservation(string $reservationId): ?ReservationInfo
    {
        $reservation = ReservationModel::query()
            ->withoutGlobalScopes()
            ->where('uuid', $reservationId)
            ->first();

        if ($reservation === null) {
            return null;
        }

        $stay = StayModel::query()
            ->withoutGlobalScopes()
            ->where('uuid', $reservation->stay_uuid)
            ->first();

        $checkIn = new DateTimeImmutable($reservation->check_in);
        $checkOut = new DateTimeImmutable($reservation->check_out);
        $nights = (int) $checkIn->diff($checkOut)->days;

        return new ReservationInfo(
            reservationId: $reservation->uuid,
            guestId: $reservation->guest_id,
            stayId: $reservation->stay_uuid,
            stayName: $stay?->name ?? '',
            accountId: $reservation->account_uuid ?? '',
            checkIn: $reservation->check_in,
            checkOut: $reservation->check_out,
            nights: $nights,
            pricePerNight: $stay?->price_per_night ?? 0.0,
        );
    }
}
