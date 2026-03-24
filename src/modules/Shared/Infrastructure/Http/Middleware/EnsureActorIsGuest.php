<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActorIsGuest
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        $user->load('types');
        $typeNames = $user->types->pluck('name')->toArray();

        if (! in_array('guest', $typeNames, true)) {
            abort(403, 'Access denied.');
        }

        // Resolve user UUID from user_id linkage
        if ($user->user_id !== null) {
            $userProfile = $this->userRepository->findByNumericId((int) $user->user_id);
            $userUuid = $userProfile ? (string) $userProfile->uuid : null;

            $request->attributes->set('guest_uuid', $userUuid);
        }

        return $next($request);
    }
}
