<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\User\Domain\UserId;
use Modules\User\Domain\Repository\UserRepository;
use Modules\User\Presentation\Http\Presenter\UserPresenter;
use Modules\IAM\Domain\HotelId;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Reservation\Application\Query\ReservationReadModel;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\Reservation;

final class PortalDashboardView
{
    public function __construct(
        private UserRepository $userRepository,
        private ReservationRepository $reservationRepository,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $user = $guestUuid
            ? $this->userRepository->findByUuid(UserId::fromString($guestUuid))
            : null;

        // Get user's reservations across all hotels
        $reservationResult = $guestUuid
            ? $this->reservationRepository->listByGuestId($guestUuid, page: 1, perPage: 5)
            : null;

        // Get featured hotels
        $hotels = array_map(fn ($hotel) => [
            'uuid' => (string) $hotel->uuid,
            'name' => $hotel->name,
            'slug' => $hotel->slug,
            'description' => $hotel->description,
            'address' => $hotel->address,
        ], $this->hotelRepository->findAll());

        return Inertia::render('Portal/Dashboard', [
            'guest' => $user ? UserPresenter::fromDomain($user) : null,
            'reservations' => $reservationResult !== null
                ? array_map(function (Reservation $r) {
                    $readModel = ReservationReadModel::fromReservation($r);
                    $hotel = $this->hotelRepository->findByUuid(HotelId::fromString($r->hotelId));
                    if ($hotel) {
                        $readModel = $readModel->withHotel([
                            'hotel_id' => (string) $hotel->uuid,
                            'name' => $hotel->name,
                            'address' => $hotel->address,
                        ]);
                    }
                    return $readModel->jsonSerialize();
                }, $reservationResult->items)
                : [],
            'reservationsMeta' => [
                'total' => $reservationResult !== null ? $reservationResult->total : 0,
            ],
            'hotels' => $hotels,
        ]);
    }
}
