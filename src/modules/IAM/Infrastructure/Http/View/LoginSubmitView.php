<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginSubmitView
{
    public function __invoke(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $user = Auth::user();
        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();

        $request->session()->regenerate();

        if (in_array('admin', $roleNames, true) || in_array('superadmin', $roleNames, true)) {
            return redirect()->intended('/dashboard');
        }

        return redirect()->intended('/portal');
    }
}
