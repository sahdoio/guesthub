<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\CreateReservationHandler;

final class PortalReservationStoreView
{
    public function __construct(
        private CreateReservationHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
        ]);

        $id = $this->handler->handle(new CreateReservation(
            guestId: $guestUuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            roomType: $data['room_type'],
        ));

        return redirect("/portal/reservations/{$id}")->with('success', 'Reservation created.');
    }
}
