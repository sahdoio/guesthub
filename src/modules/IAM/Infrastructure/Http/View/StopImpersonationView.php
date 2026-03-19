<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;

final class StopImpersonationView
{
    public function __invoke(Request $request): RedirectResponse
    {
        $originalId = $request->session()->get('impersonating_from');

        if (! $originalId) {
            return redirect('/');
        }

        $original = ActorModel::find($originalId);

        if (! $original) {
            abort(500, 'Original user not found.');
        }

        $request->session()->forget('impersonating_from');
        $request->session()->forget('tenant_account_id');

        auth()->login($original);

        return redirect('/superadmin');
    }
}
