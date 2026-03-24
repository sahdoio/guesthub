<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Integration;

use Modules\Billing\Domain\DTO\ReservationInfo;
use Modules\Billing\Domain\Service\ReservationGateway;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\StayId;

final class ReservationGatewayAdapter implements ReservationGateway
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private StayRepository $stayRepository,
    ) {}

    public function findReservation(string $reservationId): ?ReservationInfo
    {
        $reservation = $this->reservationRepository->findByUuidGlobal(
            ReservationId::fromString($reservationId),
        );

        if ($reservation === null) {
            return null;
        }

        $stay = $this->stayRepository->findByUuid(
            StayId::fromString($reservation->stayId),
        );

        $checkIn = $reservation->period->checkIn;
        $checkOut = $reservation->period->checkOut;
        $nights = (int) $checkIn->diff($checkOut)->days;

        return new ReservationInfo(
            reservationId: $reservation->uuid->value,
            guestId: $reservation->guestId,
            stayId: $reservation->stayId,
            stayName: $stay !== null ? $stay->name : '',
            accountId: $reservation->accountId,
            checkIn: $checkIn->format('Y-m-d'),
            checkOut: $checkOut->format('Y-m-d'),
            nights: $nights,
            pricePerNight: $stay !== null ? $stay->pricePerNight : 0.0,
        );
    }
}
