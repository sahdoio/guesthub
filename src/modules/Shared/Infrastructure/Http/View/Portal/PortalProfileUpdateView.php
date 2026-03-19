<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Application\Command\UpdateUser;
use Modules\User\Application\Command\UpdateUserHandler;

final class PortalProfileUpdateView
{
    public function __construct(
        private UpdateUserHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'regex:/^[1-9]\d{6,14}$/'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string', 'max:255'],
        ], [
            'phone.regex' => 'Phone must contain only digits (e.g., 5511999999999).',
        ]);

        $this->handler->handle(new UpdateUser(
            userId: $guestUuid,
            fullName: $data['full_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            preferences: $data['preferences'] ?? null,
        ));

        return redirect('/portal/profile')->with('success', 'Profile updated.');
    }
}
