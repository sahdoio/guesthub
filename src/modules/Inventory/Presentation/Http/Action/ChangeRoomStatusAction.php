<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Action;

use Modules\Inventory\Application\Command\ChangeRoomStatus;
use Modules\Inventory\Application\Command\ChangeRoomStatusHandler;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Presentation\Http\Presenter\RoomPresenter;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ChangeRoomStatusAction
{
    public function __construct(
        private ChangeRoomStatusHandler $handler,
        private RoomRepository $repository,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'status' => ['required', 'string', 'in:available,occupied,maintenance,out_of_order'],
        ]);

        $this->handler->handle(new ChangeRoomStatus(
            roomId: $uuid,
            status: $data['status'],
        ));

        $room = $this->repository->findByUuid(RoomId::fromString($uuid));

        return $this->responder->ok(['data' => RoomPresenter::fromDomain($room)]);
    }
}
