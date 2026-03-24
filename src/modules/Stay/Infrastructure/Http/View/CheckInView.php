<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Stay\Application\Command\CheckInGuest;
use Modules\Stay\Application\Command\CheckInGuestHandler;

final readonly class CheckInView
{
    public function __construct(
        private CheckInGuestHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $this->handler->handle(new CheckInGuest(
            reservationId: $id,
        ));

        return redirect("/reservations/{$id}")->with('success', 'Guest checked in.');
    }
}
