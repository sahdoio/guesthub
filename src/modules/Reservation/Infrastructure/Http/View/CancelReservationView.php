<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\CancelReservation;
use Modules\Reservation\Application\Command\CancelReservationHandler;

final class CancelReservationView
{
    public function __construct(
        private CancelReservationHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $this->handler->handle(new CancelReservation(
            reservationId: $id,
            reason: $data['reason'],
        ));

        return redirect("/reservations/{$id}")->with('success', 'Reservation cancelled.');
    }
}
