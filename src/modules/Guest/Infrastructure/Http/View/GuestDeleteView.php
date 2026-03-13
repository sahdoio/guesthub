<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Http\View;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Guest\Domain\Exception\GuestNotFoundException;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;

final class GuestDeleteView
{
    public function __construct(
        private GuestRepository $repository,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        $profile = $this->repository->findByUuid(GuestId::fromString($id))
            ?? throw GuestNotFoundException::withId(GuestId::fromString($id));

        $this->repository->remove($profile);

        return redirect('/guests')->with('success', 'Guest deleted.');
    }
}
