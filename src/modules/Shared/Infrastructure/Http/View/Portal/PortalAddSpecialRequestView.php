<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\AddSpecialRequestHandler;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;

final readonly class PortalAddSpecialRequestView
{
    public function __construct(
        private AddSpecialRequestHandler $addHandler,
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
            'type' => ['required', 'string', 'in:early_check_in,late_check_out,extra_bed,dietary_restriction,special_occasion,other'],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->addHandler->handle(new AddSpecialRequest(
            reservationId: $id,
            requestType: $data['type'],
            description: $data['description'],
        ));

        return redirect("/portal/reservations/{$id}")->with('success', 'Special request added.');
    }
}
