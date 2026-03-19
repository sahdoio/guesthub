<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\User\Domain\Repository\UserRepository;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\HotelRepository;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly UserRepository $userRepository,
        private readonly HotelRepository $hotelRepository,
    ) {}

    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->load('types');
        }

        $typeNames = $user ? $user->types->pluck('name')->values()->toArray() : [];

        $tenantData = [];

        // For owners, share their account info and hotel count
        if ($user && in_array('owner', $typeNames, true) && $user->account_id) {
            $ownerAccount = $this->accountRepository->findByNumericId((int) $user->account_id);
            if ($ownerAccount) {
                $tenantData['currentAccount'] = [
                    'uuid' => (string) $ownerAccount->uuid,
                    'name' => $ownerAccount->name,
                    'slug' => $ownerAccount->slug ?? '',
                ];
                $hotels = $this->hotelRepository->findByAccountId($ownerAccount->uuid);
                $tenantData['hasHotels'] = count($hotels) > 0;
            }
        }

        $userUuid = null;
        if ($user && in_array('guest', $typeNames, true) && $user->user_id !== null) {
            $userProfile = $this->userRepository->findByNumericId((int) $user->user_id);
            $userUuid = $userProfile ? (string) $userProfile->uuid : null;
        }

        // Impersonation state
        $impersonating = $request->session()->has('impersonating_from');

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->getKey(),
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $typeNames,
                    'guest_uuid' => $userUuid,
                ] : null,
                'impersonating' => $impersonating,
                ...$tenantData,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ]);
    }
}
