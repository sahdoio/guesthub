<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\IAM\Application\Command\RegisterHotelOwner;
use Modules\IAM\Application\Command\RegisterHotelOwnerHandler;

final class RegisterHotelSubmitView
{
    public function __construct(
        private readonly RegisterHotelOwnerHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:actors,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'regex:/^[1-9]\d{6,14}$/'],
            'document' => ['required', 'string', 'max:50'],
        ]);

        $this->handler->handle(new RegisterHotelOwner(
            ownerName: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'],
            document: $data['document'],
            accountName: $data['name']."'s Organization",
            accountSlug: Str::slug($data['name']).'-'.Str::random(6),
        ));

        Auth::attempt(['email' => $data['email'], 'password' => $data['password']]);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
