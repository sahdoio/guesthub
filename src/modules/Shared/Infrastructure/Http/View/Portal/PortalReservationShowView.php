<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;

final class PortalReservationShowView
{
    public function __construct(
        private GetReservationHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $reservation = $this->handler->handle(new GetReservation($id));

        // Enforce ownership: guest can only view their own reservations
        $guestUuid = $request->attributes->get('guest_uuid');
        $reservationGuestId = $reservation->guest['guest_id'] ?? null;

        if ($guestUuid && $reservationGuestId !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        return Inertia::render('Portal/Reservations/Show', [
            'reservation' => $reservation,
        ]);
    }
}
