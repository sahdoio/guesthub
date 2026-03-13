<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActorIsAdmin
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(403, 'Access denied.');
            }

            return redirect('/login');
        }

        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();

        if (! in_array('admin', $roleNames, true) && ! in_array('superadmin', $roleNames, true)) {
            if ($request->expectsJson()) {
                abort(403, 'Access denied.');
            }

            return redirect('/login');
        }

        // Set tenant context
        if ($user->account_id) {
            $this->tenantContext->set((int) $user->account_id);
        } elseif ($request->session()->has('tenant_account_id')) {
            $this->tenantContext->set((int) $request->session()->get('tenant_account_id'));
        }

        return $next($request);
    }
}
