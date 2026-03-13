<?php

declare(strict_types=1);

namespace Modules\Guest\Presentation\Http\Action;

use Modules\Guest\Domain\Exception\GuestNotFoundException;
use Modules\Guest\Domain\GuestId;
use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteGuestAction
{
    public function __construct(
        private GuestRepository $repository,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $guest = $this->repository->findByUuid(GuestId::fromString($uuid))
            ?? throw GuestNotFoundException::withId(GuestId::fromString($uuid));

        $this->repository->remove($guest);

        return $this->responder->noContent();
    }
}
