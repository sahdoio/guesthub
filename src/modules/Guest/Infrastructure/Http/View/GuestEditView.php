<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Guest\Domain\Exception\GuestProfileNotFoundException;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Guest\Presentation\Http\Presenter\GuestProfilePresenter;

final class GuestEditView
{
    public function __construct(
        private GuestProfileRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $profile = $this->repository->findByUuid(GuestProfileId::fromString($id))
            ?? throw GuestProfileNotFoundException::withId(GuestProfileId::fromString($id));

        return Inertia::render('Guests/Edit', [
            'guest' => GuestProfilePresenter::fromDomain($profile),
        ]);
    }
}
