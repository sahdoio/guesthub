<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Domain\Exception\GuestNotFoundException;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Guest\Presentation\Http\Presenter\GuestPresenter;
use Modules\Shared\Infrastructure\Service\AuthenticatedGuestResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowGuestAction
{
    public function __construct(
        private GuestRepository $repository,
        private AuthenticatedGuestResolver $guestResolver,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $this->enforceOwnership($uuid);

        $guest = $this->repository->findByUuid(GuestId::fromString($uuid))
            ?? throw GuestNotFoundException::withId(GuestId::fromString($uuid));

        return $this->responder->ok(['data' => GuestPresenter::fromDomain($guest)]);
    }

    private function enforceOwnership(string $uuid): void
    {
        if ($this->guestResolver->isAdminOrSuperAdmin()) {
            return;
        }

        $ownGuestUuid = $this->guestResolver->resolveGuestUuid();
        if ($ownGuestUuid !== null && $ownGuestUuid !== $uuid) {
            abort(403, 'Access denied.');
        }
    }
}
