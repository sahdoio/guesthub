<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Stay\Application\Command\CheckOutGuest;
use Modules\Stay\Application\Command\CheckOutGuestHandler;

final class CheckOutView
{
    public function __construct(
        private CheckOutGuestHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $this->handler->handle(new CheckOutGuest($id));

        return redirect("/reservations/{$id}")->with('success', 'Guest checked out.');
    }
}
