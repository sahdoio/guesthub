<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Inertia\Inertia;
use Inertia\Response;

final class PortalReservationCreateView
{
    public function __invoke(): Response
    {
        return Inertia::render('Portal/Reservations/Create');
    }
}
