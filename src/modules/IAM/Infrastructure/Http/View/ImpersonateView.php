<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\ValueObject\ActorId;

final class ImpersonateView
{
    public function __construct(
        private ActorRepository $actorRepository,
    ) {}

    public function __invoke(Request $request, int $actorId): RedirectResponse
    {
        $currentUser = $request->user();
        $currentTypeNames = $this->actorRepository->resolveTypeNames(
            ActorId::fromString($currentUser->uuid),
        );

        if (! in_array('superadmin', $currentTypeNames, true)) {
            abort(403, 'Only superadmins can impersonate.');
        }

        $target = $this->actorRepository->findByNumericId($actorId);

        if (! $target) {
            abort(404, 'User not found.');
        }

        // Store original user ID in session
        $request->session()->put('impersonating_from', $currentUser->getKey());
        $request->session()->forget('tenant_account_id');

        // Log in as the target user
        Auth::loginUsingId($actorId);

        // Redirect based on target's type
        $targetTypeNames = $this->actorRepository->resolveTypeNames($target->uuid);

        if (in_array('guest', $targetTypeNames, true)) {
            return redirect('/portal');
        }

        return redirect('/dashboard');
    }
}
