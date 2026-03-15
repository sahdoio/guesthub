<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ReservationId;
use Modules\Shared\Infrastructure\Service\AuthenticatedGuestResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowReservationAction
{
    public function __construct(
        private GetReservationHandler $handler,
        private ReservationRepository $reservationRepository,
        private AuthenticatedGuestResolver $guestResolver,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $this->enforceReservationOwnership($id);

        $readModel = $this->handler->handle(
            new GetReservation($id),
        );

        return $this->responder->ok(['data' => $readModel]);
    }

    private function enforceReservationOwnership(string $reservationUuid): void
    {
        if ($this->guestResolver->isAdminOrSuperAdmin()) {
            return;
        }

        $ownGuestUuid = $this->guestResolver->resolveGuestUuid();
        if ($ownGuestUuid !== null) {
            $reservation = $this->reservationRepository->findByUuid(ReservationId::fromString($reservationUuid));
            if ($reservation === null || $ownGuestUuid !== $reservation->guestId) {
                abort(403, 'Access denied.');
            }
        }
    }
}
