<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActorIsOwner
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccountRepository $accountRepository,
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

        $user->load('types');
        $typeNames = $user->types->pluck('name')->toArray();

        $allowedTypes = ['owner', 'superadmin'];
        if (! array_intersect($typeNames, $allowedTypes)) {
            if ($request->expectsJson()) {
                abort(403, 'Access denied.');
            }

            return redirect('/login');
        }

        // Set tenant context from user's account
        if ($user->account_id) {
            $account = $this->accountRepository->findByNumericId((int) $user->account_id);
            if ($account !== null) {
                $this->tenantContext->set($account->uuid->value);
            }
        }

        return $next($request);
    }
}
