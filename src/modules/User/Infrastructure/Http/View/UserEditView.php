<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\User\Domain\Exception\UserNotFoundException;
use Modules\User\Domain\UserId;
use Modules\User\Domain\Repository\UserRepository;
use Modules\User\Presentation\Http\Presenter\UserPresenter;

final class UserEditView
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $user = $this->repository->findByUuid(UserId::fromString($id))
            ?? throw UserNotFoundException::withUuid($id);

        return Inertia::render('Guests/Edit', [
            'guest' => UserPresenter::fromDomain($user),
        ]);
    }
}
