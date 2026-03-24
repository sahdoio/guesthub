<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Stay\Application\Query\GetReservation;
use Modules\Stay\Application\Query\GetReservationHandler;

final class ReservationShowView
{
    public function __construct(
        private GetReservationHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $reservation = $this->handler->handle(new GetReservation($id));

        return Inertia::render('Reservations/Show', [
            'reservation' => $reservation,
        ]);
    }
}
