<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Domain\Exception\UserNotFoundException;
use Modules\User\Domain\UserId;
use Modules\User\Domain\Repository\UserRepository;

final class UserDeleteView
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $user = $this->repository->findByUuid(UserId::fromString($id))
            ?? throw UserNotFoundException::withUuid($id);

        $this->repository->remove($user);

        return redirect('/guests')->with('success', 'Guest deleted.');
    }
}
