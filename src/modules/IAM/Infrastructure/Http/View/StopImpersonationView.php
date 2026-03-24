<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\IAM\Domain\Repository\ActorRepository;

final class StopImpersonationView
{
    public function __construct(
        private ActorRepository $actorRepository,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $originalId = $request->session()->get('impersonating_from');

        if (! $originalId) {
            return redirect('/');
        }

        $original = $this->actorRepository->findByNumericId($originalId);

        if (! $original) {
            abort(500, 'Original user not found.');
        }

        $request->session()->forget('impersonating_from');
        $request->session()->forget('tenant_account_id');

        Auth::loginUsingId($originalId);

        return redirect('/superadmin');
    }
}
