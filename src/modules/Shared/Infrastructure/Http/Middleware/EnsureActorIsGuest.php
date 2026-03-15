<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActorIsGuest
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly GuestRepository $guestRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect('/login');
        }

        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();

        if (! in_array('guest', $roleNames, true)) {
            abort(403, 'Access denied.');
        }

        // Set tenant context from account
        if ($user->account_id) {
            $this->tenantContext->set((int) $user->account_id);
        }

        // Resolve guest UUID from subject linkage
        if ($user->subject_type === 'guest' && $user->subject_id !== null) {
            $guest = $this->guestRepository->findByNumericId((int) $user->subject_id);
            $guestUuid = $guest ? (string) $guest->uuid : null;

            $request->attributes->set('guest_uuid', $guestUuid);
        }

        return $next($request);
    }
}
