<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Integration;

use Modules\Billing\Domain\DTO\ReservationInfo;
use Modules\Billing\Domain\Service\ReservationGateway;
use Modules\Stay\Infrastructure\Integration\StayApi;

final class ReservationGatewayAdapter implements ReservationGateway
{
    public function __construct(
        private StayApi $stayApi,
    ) {}

    public function findReservation(string $reservationId): ?ReservationInfo
    {
        $data = $this->stayApi->findReservation($reservationId);

        if ($data === null) {
            return null;
        }

        return new ReservationInfo(
            reservationId: $data->reservationId,
            guestId: $data->guestId,
            stayId: $data->stayId,
            stayName: $data->stayName,
            accountId: $data->accountId,
            checkIn: $data->checkIn,
            checkOut: $data->checkOut,
            nights: $data->nights,
            pricePerNight: $data->pricePerNight,
        );
    }
}
