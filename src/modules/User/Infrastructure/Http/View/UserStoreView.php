<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Application\Command\CreateUser;
use Modules\User\Application\Command\CreateUserHandler;

final class UserStoreView
{
    public function __construct(
        private CreateUserHandler $handler,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[1-9]\d{6,14}$/'],
            'document' => ['required', 'string', 'max:50'],
        ], [
            'phone.regex' => 'Phone must contain only digits (e.g., 5511999999999).',
        ]);

        $id = $this->handler->handle(new CreateUser(
            fullName: $data['full_name'],
            email: $data['email'],
            phone: $data['phone'],
            document: $data['document'],
        ));

        return redirect("/guests/{$id}")->with('success', 'Guest created.');
    }
}
