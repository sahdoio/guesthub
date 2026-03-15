<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\AddSpecialRequestHandler;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ReservationId;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Shared\Infrastructure\Service\AuthenticatedGuestResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AddSpecialRequestAction
{
    public function __construct(
        private AddSpecialRequestHandler $handler,
        private ReservationRepository $reservationRepository,
        private AuthenticatedGuestResolver $guestResolver,
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
