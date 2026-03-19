<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use DateMalformedStringException;
use DateTimeImmutable;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\CreateReservationHandler;
use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateReservationAction
{
    public function __construct(
        private CreateReservationHandler $handler,
        private GetReservationHandler $queryHandler,
        private AuthenticatedUserResolver $userResolver,
        private InputValidator $validator,
        private JsonResponder $responder,
        private TenantContext $tenantContext,
        private AccountRepository $accountRepository,
        private HotelRepository $hotelRepository,
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

        $account = $this->accountRepository->findByNumericId($this->tenantContext->id());
        $hotels = $this->hotelRepository->findByAccountId($account->uuid);
        $hotel = $hotels[0] ?? throw new \DomainException('No hotel found for this account.');

        $id = $this->handler->handle(new CreateReservation(
            guestId: $data['guest_id'],
            accountId: (string) $account->uuid,
            hotelId: (string) $hotel->uuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            roomType: $data['room_type'],
        ));

        $readModel = $this->queryHandler->handle(new GetReservation((string) $id));

        return $this->responder->created(['data' => $readModel]);
    }

    private function enforceGuestOwnership(string $guestUuid): void
    {
        if ($this->userResolver->isOwnerOrSuperAdmin()) {
            return;
        }

        $ownGuestUuid = $this->userResolver->resolveUserUuid();
        if ($ownGuestUuid !== null && $ownGuestUuid !== $guestUuid) {
            abort(403, 'Access denied.');
        }
    }
}
