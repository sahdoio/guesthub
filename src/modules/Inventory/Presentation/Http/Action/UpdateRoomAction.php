<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Action;

use Modules\Inventory\Application\Command\UpdateRoom;
use Modules\Inventory\Application\Command\UpdateRoomHandler;
use Modules\Inventory\Domain\Repository\RoomRepository;
use Modules\Inventory\Domain\RoomId;
use Modules\Inventory\Presentation\Http\Presenter\RoomPresenter;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateRoomAction
{
    public function __construct(
        private UpdateRoomHandler $handler,
        private RoomRepository $repository,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'price_per_night' => ['sometimes', 'numeric', 'min:0'],
            'amenities' => ['sometimes', 'array'],
            'amenities.*' => ['string', 'max:255'],
        ]);

        $this->handler->handle(new UpdateRoom(
            roomId: $uuid,
            pricePerNight: isset($data['price_per_night']) ? (float) $data['price_per_night'] : null,
            amenities: $data['amenities'] ?? null,
        ));

        $room = $this->repository->findByUuid(RoomId::fromString($uuid));

        return $this->responder->ok(['data' => RoomPresenter::fromDomain($room)]);
    }
}
