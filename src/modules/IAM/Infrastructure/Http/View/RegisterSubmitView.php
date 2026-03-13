<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\IAM\Application\Command\RegisterActor;
use Modules\IAM\Application\Command\RegisterActorHandler;
use Modules\IAM\Infrastructure\Persistence\Eloquent\ActorModel;

final class RegisterSubmitView
{
    public function __construct(
        private readonly RegisterActorHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:actors,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'document' => ['required', 'string', 'max:50'],
        ]);

        $this->handler->handle(new RegisterActor(
            accountName: $data['name'] . "'s Account",
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'],
            document: $data['document'],
        ));

        // Log the user in after registration
        $actorModel = ActorModel::where('email', $data['email'])->first();
        Auth::login($actorModel);
        $request->session()->regenerate();

        return redirect('/dashboard');
    }
}
