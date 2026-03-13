<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\Reservation\Application\Command\CancelReservation;
use Modules\Reservation\Application\Command\CancelReservationHandler;
use Modules\Reservation\Infrastructure\Persistence\Eloquent\ReservationModel;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CancelReservationAction
{
    public function __construct(
        private CancelReservationHandler $handler,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $this->enforceReservationOwnership($id);

        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $this->handler->handle(new CancelReservation(
            reservationId: $id,
            reason: $data['reason'],
        ));

        return $this->responder->ok(['message' => 'Reservation cancelled.']);
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
