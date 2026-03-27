<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Stay\Application\Command\CheckInGuest;
use Modules\Stay\Application\Command\CheckInGuestHandler;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

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

        if (! $this->isAuthorizedForReservation($id)) {
            return $this->responder->error(['message' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $this->handler->handle(new CheckInGuest(
            reservationId: $id,
        ));

        return $this->responder->ok(['message' => 'Guest checked in.']);
    }

    private function isAuthorizedForReservation(string $reservationUuid): bool
    {
        if ($this->userResolver->isOwnerOrSuperAdmin()) {
            return true;
        }

        $ownGuestUuid = $this->userResolver->resolveUserUuid();
        if ($ownGuestUuid === null) {
            return true;
        }

        $reservation = $this->reservationRepository->findByUuid(ReservationId::fromString($reservationUuid));

        return $reservation !== null && $ownGuestUuid === $reservation->guestId;
    }
}
