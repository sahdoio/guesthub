<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Inertia\Inertia;
use Inertia\Response;

final class HotelCreateView
{
    public function __invoke(): Response
    {
        return Inertia::render('Hotels/Create');
    }
}
