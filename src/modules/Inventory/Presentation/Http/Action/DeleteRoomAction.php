<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Action;

use Modules\Inventory\Domain\Exception\RoomNotFoundException;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeleteRoomAction
{
    public function __construct(
        private RoomRepository $repository,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        $room = $this->repository->findByUuid(RoomId::fromString($uuid))
            ?? throw RoomNotFoundException::withId(RoomId::fromString($uuid));

        $this->repository->remove($room);

        return $this->responder->noContent();
    }
}
