<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Domain\Exception\GuestProfileNotFoundException;
use Modules\Guest\Domain\GuestProfileId;
use Modules\Guest\Domain\Repository\GuestProfileRepository;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteGuestProfileAction
{
    public function __construct(
        private GuestProfileRepository $repository,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $profile = $this->repository->findByUuid(GuestProfileId::fromString($uuid))
            ?? throw GuestProfileNotFoundException::withId(GuestProfileId::fromString($uuid));

        $this->repository->remove($profile);

        return JsonResponder::noContent();
    }
}
