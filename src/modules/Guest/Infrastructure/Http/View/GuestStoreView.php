<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Guest\Application\Command\CreateGuestProfile;
use Modules\Guest\Application\Command\CreateGuestProfileHandler;

final class GuestStoreView
{
    public function __construct(
        private CreateGuestProfileHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'document' => ['required', 'string', 'max:50'],
        ], [
            'phone.regex' => 'Phone must be in E.164 format (e.g., +5511999999999).',
        ]);

        $id = $this->handler->handle(new CreateGuestProfile(
            fullName: $data['full_name'],
            email: $data['email'],
            phone: $data['phone'],
            document: $data['document'],
        ));

        return redirect("/guests/{$id}")->with('success', 'Guest profile created.');
    }
}
