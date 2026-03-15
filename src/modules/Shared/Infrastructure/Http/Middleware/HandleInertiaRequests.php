<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\IAM\Domain\Repository\AccountRepository;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly GuestRepository $guestRepository,
    ) {}

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
            $accounts = array_map(fn ($account) => [
                'uuid' => (string) $account->uuid,
                'name' => $account->name,
            ], $this->accountRepository->findAll());

            $currentAccountId = $request->session()->get('tenant_account_id');
            $currentAccount = null;
            if ($currentAccountId) {
                $account = $this->accountRepository->findByNumericId((int) $currentAccountId);
                $currentAccount = $account ? [
                    'uuid' => (string) $account->uuid,
                    'name' => $account->name,
                ] : null;
            }

            $tenantData = [
                'accounts' => $accounts,
                'currentAccount' => $currentAccount,
            ];
        }

        $guestUuid = null;
        if ($user && in_array('guest', $roleNames, true)
            && $user->subject_type === 'guest' && $user->subject_id !== null) {
            $guest = $this->guestRepository->findByNumericId((int) $user->subject_id);
            $guestUuid = $guest ? (string) $guest->uuid : null;
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $roleNames,
                    'guest_uuid' => $guestUuid,
                ] : null,
                ...$tenantData,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ]);
    }
}
