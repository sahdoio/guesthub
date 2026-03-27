<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Modules\Stay\Application\Command\CancelReservation;
use Modules\Stay\Application\Command\CancelReservationHandler;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

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

        if (! $this->isAuthorizedForReservation($id)) {
            return $this->responder->error(['message' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $this->handler->handle(new CancelReservation(
            reservationId: $id,
            reason: $data['reason'],
        ));

        return $this->responder->ok(['message' => 'Reservation cancelled.']);
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
