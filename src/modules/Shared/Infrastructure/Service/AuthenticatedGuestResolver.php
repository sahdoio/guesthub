<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Service;

use Modules\Guest\Domain\Repository\GuestRepository;

final readonly class AuthenticatedGuestResolver
{
    public function __construct(
        private GuestRepository $guestRepository,
    ) {}

    public function resolveGuestUuid(): ?string
    {
        $user = auth()->user();

        if (! $user || $user->subject_type !== 'guest' || $user->subject_id === null) {
            return null;
        }

        $guest = $this->guestRepository->findByNumericId((int) $user->subject_id);

        return $guest ? (string) $guest->uuid : null;
    }

    public function isGuestRole(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();

        return in_array('guest', $roleNames, true);
    }

    public function isAdminOrSuperAdmin(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();

        return in_array('admin', $roleNames, true) || in_array('superadmin', $roleNames, true);
    }
}
