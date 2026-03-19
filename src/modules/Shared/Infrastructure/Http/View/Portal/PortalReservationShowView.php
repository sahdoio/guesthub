<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\HotelId;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Reservation\Domain\Exception\ReservationNotFoundException;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\Service\GuestGateway;
use Modules\Reservation\Application\Query\ReservationReadModel;

final class PortalReservationShowView
{
    public function __construct(
        private ReservationRepository $repository,
        private GuestGateway $guestGateway,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $reservationId = ReservationId::fromString($id);
        $reservation = $this->repository->findByUuidGlobal($reservationId)
            ?? throw ReservationNotFoundException::withId($reservationId);

        // Enforce ownership
        $guestUuid = $request->attributes->get('guest_uuid');
        if ($guestUuid && $reservation->guestId !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        $readModel = ReservationReadModel::fromReservation($reservation);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestId);
        if ($guestInfo) {
            $readModel = $readModel->withGuest([
                'guest_id' => $guestInfo->guestId,
                'full_name' => $guestInfo->fullName,
                'email' => $guestInfo->email,
                'phone' => $guestInfo->phone,
                'document' => $guestInfo->document,
                'is_vip' => $guestInfo->isVip,
            ]);
        }

        $hotel = $this->hotelRepository->findByUuid(HotelId::fromString($reservation->hotelId));
        if ($hotel) {
            $readModel = $readModel->withHotel([
                'hotel_id' => (string) $hotel->uuid,
                'name' => $hotel->name,
                'address' => $hotel->address,
            ]);
        }

        return Inertia::render('Portal/Reservations/Show', [
            'reservation' => $readModel,
        ]);
    }
}
