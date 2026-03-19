<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActorType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $user->load('types');
        $userTypes = $user->types->pluck('name')->toArray();

        foreach ($types as $type) {
            if (in_array($type, $userTypes, true)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied.');
    }
}
