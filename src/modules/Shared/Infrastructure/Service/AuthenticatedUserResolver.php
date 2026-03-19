<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Service;

use Modules\User\Domain\Repository\UserRepository;

final readonly class AuthenticatedUserResolver
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function resolveUserUuid(): ?string
    {
        $user = auth()->user();

        if (! $user || $user->user_id === null) {
            return null;
        }

        $userProfile = $this->userRepository->findByNumericId((int) $user->user_id);

        return $userProfile ? (string) $userProfile->uuid : null;
    }

    public function isGuestType(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $user->load('types');
        $typeNames = $user->types->pluck('name')->toArray();

        return in_array('guest', $typeNames, true);
    }

    public function isOwnerOrSuperAdmin(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $user->load('types');
        $typeNames = $user->types->pluck('name')->toArray();

        return in_array('owner', $typeNames, true) || in_array('superadmin', $typeNames, true);
    }
}
