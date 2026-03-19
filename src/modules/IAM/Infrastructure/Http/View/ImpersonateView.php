<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;

final class ImpersonateView
{
    public function __invoke(Request $request, int $actorId): RedirectResponse
    {
        $user = $request->user();
        $user->load('types');
        $typeNames = $user->types->pluck('name')->toArray();

        if (! in_array('superadmin', $typeNames, true)) {
            abort(403, 'Only superadmins can impersonate.');
        }

        $target = ActorModel::find($actorId);

        if (! $target) {
            abort(404, 'User not found.');
        }

        // Store original user ID in session
        $request->session()->put('impersonating_from', $user->getKey());
        $request->session()->forget('tenant_account_id');

        // Log in as the target user
        auth()->login($target);

        // Redirect based on target's type
        $target->load('types');
        $targetTypes = $target->types->pluck('name')->toArray();

        if (in_array('guest', $targetTypes, true)) {
            return redirect('/portal');
        }

        return redirect('/dashboard');
    }
}
