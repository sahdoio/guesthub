<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LoginView
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Auth/Login');
    }
}
