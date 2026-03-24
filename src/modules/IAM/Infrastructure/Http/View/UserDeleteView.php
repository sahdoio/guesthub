<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Exception\UserNotFoundException;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\UserId;

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
