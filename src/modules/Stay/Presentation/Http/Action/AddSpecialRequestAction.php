<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Modules\Stay\Application\Command\AddSpecialRequest;
use Modules\Stay\Application\Command\AddSpecialRequestHandler;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\RequestType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AddSpecialRequestAction
{
    public function __construct(
        private AddSpecialRequestHandler $handler,
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
