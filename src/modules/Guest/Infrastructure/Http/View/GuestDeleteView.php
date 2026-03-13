<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Guest\Domain\Exception\GuestProfileNotFoundException;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;

final class GuestDeleteView
{
    public function __construct(
        private GuestProfileRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $profile = $this->repository->findByUuid(GuestProfileId::fromString($id))
            ?? throw GuestProfileNotFoundException::withId(GuestProfileId::fromString($id));

        $this->repository->remove($profile);

        return redirect('/guests')->with('success', 'Guest profile deleted.');
    }
}
