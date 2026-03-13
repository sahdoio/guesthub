<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use DomainException;
use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\Reservation\Application\Command\CheckInGuest;
use Modules\Reservation\Application\Command\CheckInGuestHandler;
use Modules\Reservation\Domain\Service\InventoryGateway;
use Modules\Reservation\Infrastructure\Persistence\Eloquent\ReservationModel;
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
        $id = $request->getAttribute('id');

        $this->enforceReservationOwnership($id);

        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'room_number' => ['required', 'string', 'regex:/^\d{1,4}[A-Za-z]?$/'],
        ], [
            'room_number.regex' => 'Room number must be 1-4 digits optionally followed by a letter (e.g., 201, 101A).',
        ]);

        if (!$this->inventoryGateway->isRoomAvailable($data['room_number'])) {
            throw new DomainException('The selected room is not available.');
        }

        $this->handler->handle(new CheckInGuest(
            reservationId: $id,
            roomNumber: $data['room_number'],
        ));

        return $this->responder->ok(['message' => 'Guest checked in.']);
    }

    private function enforceReservationOwnership(string $reservationUuid): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }
        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();
        if (in_array('admin', $roleNames, true) || in_array('superadmin', $roleNames, true)) {
            return;
        }
        if ($user->subject_type === 'guest' && $user->subject_id) {
            $ownGuestUuid = GuestModel::where('id', $user->subject_id)->value('uuid');
            $reservationGuestId = ReservationModel::where('uuid', $reservationUuid)->value('guest_id');
            if ($ownGuestUuid !== $reservationGuestId) {
                abort(403, 'Access denied.');
            }
        }
    }
}
