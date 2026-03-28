<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Application\Query\ReservationReadModel;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\StayId;

final class PortalDashboardView
{
    public function __construct(
        private StayRepository $stayRepository,
        private ReservationRepository $reservationRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $stayEntities = $this->stayRepository->findAll(limit: 8);

        $disk = Storage::disk(config('filesystems.stays_disk', 'public'));

        $stays = array_map(fn ($stay) => [
            'uuid' => (string) $stay->uuid,
            'name' => $stay->name,
            'slug' => $stay->slug,
            'description' => $stay->description,
            'address' => $stay->address,
            'type' => $stay->type->value,
            'category' => $stay->category->value,
            'price_per_night' => $stay->pricePerNight,
            'capacity' => $stay->capacity,
            'contact_email' => $stay->contactEmail,
            'contact_phone' => $stay->contactPhone,
            'cover_image_url' => $stay->coverImagePath ? $disk->url($stay->coverImagePath) : null,
        ], $stayEntities);

        $guestUuid = $request->attributes->get('guest_uuid');
        $upcomingReservations = [];

        if ($guestUuid) {
            $result = $this->reservationRepository->listUpcomingByGuestId($guestUuid);

            $stayCache = [];
            $upcomingReservations = array_map(function (Reservation $r) use (&$stayCache, $disk) {
                $readModel = ReservationReadModel::fromReservation($r);

                if (! isset($stayCache[$r->stayId])) {
                    $stay = $this->stayRepository->findByUuid(StayId::fromString($r->stayId));
                    $stayCache[$r->stayId] = $stay ? [
                        'stay_id' => (string) $stay->uuid,
                        'name' => $stay->name,
                        'address' => $stay->address,
                        'cover_image_url' => $stay->coverImagePath ? $disk->url($stay->coverImagePath) : null,
                    ] : ['stay_id' => $r->stayId, 'name' => null, 'address' => null, 'cover_image_url' => null];
                }

                return $readModel->withStay($stayCache[$r->stayId])->jsonSerialize();
            }, $result->items);
        }

        return Inertia::render('Portal/Dashboard', [
            'stays' => $stays,
            'upcomingReservations' => $upcomingReservations,
        ]);
    }
}
