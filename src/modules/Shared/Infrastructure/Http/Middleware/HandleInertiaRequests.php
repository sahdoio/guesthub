<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->load('roles');
        }

        $roleNames = $user ? $user->roles->pluck('name')->values()->toArray() : [];
        $isSuperAdmin = in_array('superadmin', $roleNames, true);

        $tenantData = [];
        if ($user && $isSuperAdmin) {
            $accounts = AccountModel::orderBy('name')->get(['id', 'uuid', 'name'])->toArray();
            $currentAccountId = $request->session()->get('tenant_account_id');
            $currentAccount = $currentAccountId
                ? AccountModel::where('id', $currentAccountId)->first(['id', 'uuid', 'name'])?->toArray()
                : null;

            $tenantData = [
                'accounts' => $accounts,
                'currentAccount' => $currentAccount,
            ];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roleNames,
                ] : null,
                ...$tenantData,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ]);
    }
}
