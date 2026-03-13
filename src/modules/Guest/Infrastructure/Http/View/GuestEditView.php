<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Domain\Exception\GuestNotFoundException;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;

final class GuestEditView
{
    public function __construct(
        private GuestRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $profile = $this->repository->findByUuid(GuestId::fromString($id))
            ?? throw GuestNotFoundException::withId(GuestId::fromString($id));

        return Inertia::render('Guests/Edit', [
            'guest' => GuestPresenter::fromDomain($profile),
        ]);
    }
}
