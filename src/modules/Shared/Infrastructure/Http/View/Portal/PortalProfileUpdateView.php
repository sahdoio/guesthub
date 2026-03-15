<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Guest\Application\Command\UpdateGuest;
use Modules\Guest\Application\Command\UpdateGuestHandler;

final class PortalProfileUpdateView
{
    public function __construct(
        private UpdateGuestHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string', 'max:255'],
        ], [
            'phone.regex' => 'Phone must be in E.164 format (e.g., +5511999999999).',
        ]);

        $this->handler->handle(new UpdateGuest(
            guestId: $guestUuid,
            fullName: $data['full_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            preferences: $data['preferences'] ?? null,
        ));

        return redirect('/portal/profile')->with('success', 'Profile updated.');
    }
}
