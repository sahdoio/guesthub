<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LandingPageView
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Landing');
    }
}
