<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\CreateReservationHandler;

final class ReservationStoreView
{
    public function __construct(
        private CreateReservationHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_profile_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
        ]);

        $id = $this->handler->handle(new CreateReservation(
            guestProfileId: $data['guest_profile_id'],
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            roomType: $data['room_type'],
        ));

        return redirect("/reservations/{$id}")->with('success', 'Reservation created.');
    }
}
