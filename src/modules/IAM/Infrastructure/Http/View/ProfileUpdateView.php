<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProfileUpdateView
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = $request->user();
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'updated_at' => now(),
        ]);

        return redirect('/profile')->with('success', 'Profile updated.');
    }
}
