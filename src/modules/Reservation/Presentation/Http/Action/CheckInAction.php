<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use DomainException;
use Modules\Reservation\Application\Command\CheckInGuest;
use Modules\Reservation\Application\Command\CheckInGuestHandler;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CheckInAction
{
    public function __construct(
        private CheckInGuestHandler $handler,
        private InventoryGateway $inventoryGateway,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'room_number' => ['required', 'string', 'regex:/^\d{1,4}[A-Za-z]?$/'],
        ], [
            'room_number.regex' => 'Room number must be 1-4 digits optionally followed by a letter (e.g., 201, 101A).',
        ]);

        if (!$this->inventoryGateway->isRoomAvailable($data['room_number'])) {
            throw new DomainException('The selected room is not available.');
        }

        $this->handler->handle(new CheckInGuest(
            reservationId: $request->getAttribute('id'),
            roomNumber: $data['room_number'],
        ));

        return $this->responder->ok(['message' => 'Guest checked in.']);
    }
}
