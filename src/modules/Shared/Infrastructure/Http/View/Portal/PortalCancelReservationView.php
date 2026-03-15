<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\CancelReservation;
use Modules\Reservation\Application\Command\CancelReservationHandler;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;

final class PortalCancelReservationView
{
    public function __construct(
        private CancelReservationHandler $cancelHandler,
        private GetReservationHandler $queryHandler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        // Enforce ownership
        $reservation = $this->queryHandler->handle(new GetReservation($id));
        $guestUuid = $request->attributes->get('guest_uuid');

        if ($guestUuid && ($reservation->guest['guest_id'] ?? null) !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $this->cancelHandler->handle(new CancelReservation(
            reservationId: $id,
            reason: $data['reason'],
        ));

        return redirect("/portal/reservations/{$id}")->with('success', 'Reservation cancelled.');
    }
}
