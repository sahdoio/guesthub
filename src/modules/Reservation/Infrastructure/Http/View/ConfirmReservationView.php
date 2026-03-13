<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Reservation\Application\Command\ConfirmReservation;
use Modules\Reservation\Application\Command\ConfirmReservationHandler;

final class ConfirmReservationView
{
    public function __construct(
        private ConfirmReservationHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $this->handler->handle(new ConfirmReservation($id));

        return redirect("/reservations/{$id}")->with('success', 'Reservation confirmed.');
    }
}
