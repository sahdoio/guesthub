<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Command\CancelReservation;
use Modules\Reservation\Application\Command\CancelReservationHandler;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ReservationId;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CancelReservationAction
{
    public function __construct(
        private CancelReservationHandler $handler,
        private ReservationRepository $reservationRepository,
        private AuthenticatedUserResolver $userResolver,
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
        if ($this->userResolver->isOwnerOrSuperAdmin()) {
            return;
        }

        $ownGuestUuid = $this->userResolver->resolveUserUuid();
        if ($ownGuestUuid !== null) {
            $reservation = $this->reservationRepository->findByUuid(ReservationId::fromString($reservationUuid));
            if ($reservation === null || $ownGuestUuid !== $reservation->guestId) {
                abort(403, 'Access denied.');
            }
        }
    }
}
