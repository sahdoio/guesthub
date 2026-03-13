<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\View;

use Inertia\Inertia;
use Inertia\Response;

final class RoomCreateView
{
    public function __invoke(): Response
    {
        return Inertia::render('Rooms/Create');
    }
}
