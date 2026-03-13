<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use DateMalformedStringException;
use DateTimeImmutable;
use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\CreateReservationHandler;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateReservationAction
{
    public function __construct(
        private CreateReservationHandler $handler,
        private GetReservationHandler $queryHandler,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'guest_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
        ]);

        $this->enforceGuestOwnership($data['guest_id']);

        $id = $this->handler->handle(new CreateReservation(
            guestId: $data['guest_id'],
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            roomType: $data['room_type'],
        ));

        $readModel = $this->queryHandler->handle(new GetReservation((string) $id));

        return $this->responder->created(['data' => $readModel]);
    }

    private function enforceGuestOwnership(string $guestUuid): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }
        $user->load('roles');
        $roleNames = $user->roles->pluck('name')->toArray();
        if (in_array('admin', $roleNames, true) || in_array('superadmin', $roleNames, true)) {
            return;
        }
        // Guest role: can only create reservations for themselves
        if ($user->subject_type === 'guest' && $user->subject_id) {
            $ownGuestUuid = GuestModel::where('id', $user->subject_id)->value('uuid');
            if ($ownGuestUuid !== $guestUuid) {
                abort(403, 'Access denied.');
            }
        }
    }
}
