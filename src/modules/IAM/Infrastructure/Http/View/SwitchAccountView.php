<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SwitchAccountView
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);

        $user = $request->user();
        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();

        if (! in_array('superadmin', $roleNames, true)) {
            abort(403, 'Only superadmins can switch accounts.');
        }

        $request->session()->put('tenant_account_id', (int) $request->input('account_id'));

        return redirect()->back()->with('success', 'Switched account successfully.');
    }
}
