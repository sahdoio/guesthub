<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class MapRouteParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        foreach ($request->route()?->parameters() ?? [] as $key => $value) {
            $request->attributes->set($key, $value);
        }

        if ($user = $request->user()) {
            $request->attributes->set('user', $user);
        }

        return $next($request);
    }
}
