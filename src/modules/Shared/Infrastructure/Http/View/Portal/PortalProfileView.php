<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;

final class PortalProfileView
{
    public function __construct(
        private GuestRepository $repository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $guestUuid = $request->attributes->get('guest_uuid');
        $guest = $this->repository->findByUuid(GuestId::fromString($guestUuid));

        return Inertia::render('Portal/Profile/Show', [
            'guest' => $guest ? GuestPresenter::fromDomain($guest) : null,
        ]);
    }
}
