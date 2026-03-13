<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Guest\Infrastructure\Persistence\Eloquent\GuestModel;
use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\AddSpecialRequestHandler;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Infrastructure\Persistence\Eloquent\ReservationModel;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AddSpecialRequestAction
{
    public function __construct(
        private AddSpecialRequestHandler $handler,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $this->enforceReservationOwnership($id);

        $validTypes = implode(',', array_column(RequestType::cases(), 'value'));

        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'type' => ['required', 'string', "in:{$validTypes}"],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $requestId = $this->handler->handle(new AddSpecialRequest(
            reservationId: $id,
            requestType: $data['type'],
            description: $data['description'],
        ));

        return $this->responder->created([
            'message' => 'Special request added.',
            'request_id' => (string) $requestId,
        ]);
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
