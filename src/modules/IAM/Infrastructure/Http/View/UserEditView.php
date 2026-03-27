<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Exception\UserNotFoundException;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;

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
