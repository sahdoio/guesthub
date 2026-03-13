<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Guest\Application\Command\UpdateGuestProfile;
use Modules\Guest\Application\Command\UpdateGuestProfileHandler;

final class GuestUpdateView
{
    public function __construct(
        private UpdateGuestProfileHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'regex:/^\+[1-9]\d{6,14}$/'],
            'loyalty_tier' => ['sometimes', 'string', 'in:bronze,silver,gold,platinum'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => ['string', 'max:255'],
        ], [
            'phone.regex' => 'Phone must be in E.164 format (e.g., +5511999999999).',
        ]);

        $this->handler->handle(new UpdateGuestProfile(
            guestProfileId: $id,
            fullName: $data['full_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            loyaltyTier: $data['loyalty_tier'] ?? null,
            preferences: $data['preferences'] ?? null,
        ));

        return redirect("/guests/{$id}")->with('success', 'Guest profile updated.');
    }
}
