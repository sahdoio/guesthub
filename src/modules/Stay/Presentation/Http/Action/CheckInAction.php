<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use Modules\Stay\Application\Command\CheckInGuest;
use Modules\Stay\Application\Command\CheckInGuestHandler;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CheckInAction
{
    public function __construct(
        private CheckInGuestHandler $handler,
        private ReservationRepository $reservationRepository,
        private AuthenticatedUserResolver $userResolver,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $this->enforceReservationOwnership($id);

        $this->handler->handle(new CheckInGuest(
            reservationId: $id,
        ));

        return $this->responder->ok(['message' => 'Guest checked in.']);
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
