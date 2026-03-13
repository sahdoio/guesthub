<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Inertia\Inertia;
use Inertia\Response;

final class GuestCreateView
{
    public function __invoke(): Response
    {
        return Inertia::render('Guests/Create');
    }
}
