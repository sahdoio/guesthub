<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\IAM\Domain\Repository\UserRepository;
use Modules\IAM\Domain\ValueObject\UserId;
use Modules\IAM\Presentation\Http\Presenter\UserPresenter;

final class PortalProfileView
{
    public function __construct(
        private UserRepository $repository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guestUuid = $request->attributes->get('guest_uuid');
        $user = $this->repository->findByUuid(UserId::fromString($guestUuid));

        return Inertia::render('Portal/Profile/Show', [
            'guest' => $user ? UserPresenter::fromDomain($user) : null,
        ]);
    }
}
