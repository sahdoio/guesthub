<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AccountRepository $accountRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->account_id) {
            $this->tenantContext->set((int) $user->account_id);
        } elseif ($request->session()->has('tenant_account_id')) {
            $this->tenantContext->set((int) $request->session()->get('tenant_account_id'));
        } else {
            $user->load('types');
            $typeNames = $user->types->pluck('name')->toArray();

            if (in_array('superadmin', $typeNames, true)) {
                $accounts = $this->accountRepository->findAll();
                if ($accounts !== []) {
                    $defaultId = $this->accountRepository->resolveNumericId($accounts[0]->uuid);
                    if ($defaultId !== null) {
                        $this->tenantContext->set($defaultId);
                        $request->session()->put('tenant_account_id', $defaultId);
                    }
                }
            }
        }

        return $next($request);
    }
}
