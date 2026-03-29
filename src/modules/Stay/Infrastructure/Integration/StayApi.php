<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Integration;

use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\StayId;
use Modules\Stay\Infrastructure\Integration\Dto\ReservationData;
use Modules\Stay\Infrastructure\Integration\Dto\StayData;

final class StayApi
{
    public function __construct(
        private StayRepository $stayRepository,
        private ReservationRepository $reservationRepository,
    ) {}

    public function findByUuid(string $uuid): ?StayData
    {
        $stay = $this->stayRepository->findByUuid(StayId::fromString($uuid));

        if ($stay === null) {
            return null;
        }

        return new StayData(
            uuid: (string) $stay->uuid,
            name: $stay->name,
            slug: $stay->slug,
            type: $stay->type->value,
            category: $stay->category->value,
            pricePerNight: $stay->pricePerNight,
            capacity: $stay->capacity,
            status: $stay->status,
            description: $stay->description,
            address: $stay->address,
            amenities: $stay->amenities,
        );
    }

    public function isAvailable(string $uuid): bool
    {
        $stay = $this->stayRepository->findByUuid(StayId::fromString($uuid));

        return $stay !== null && $stay->status === 'active';
    }

    public function findReservation(string $reservationId): ?ReservationData
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

        return new ReservationData(
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
