<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use DateMalformedStringException;
use DateTimeImmutable;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Stay\Application\Command\CreateReservation;
use Modules\Stay\Application\Command\CreateReservationHandler;
use Modules\Stay\Application\Query\GetReservation;
use Modules\Stay\Application\Query\GetReservationHandler;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Shared\Infrastructure\Service\AuthenticatedUserResolver;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;
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
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'guest_id' => ['required', 'uuid'],
            'stay_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'babies' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'pets' => ['sometimes', 'integer', 'min:0', 'max:5'],
        ]);

        $this->enforceGuestOwnership($data['guest_id']);

        $account = $this->accountRepository->findByNumericId($this->tenantContext->id());

        $stay = StayModel::query()
            ->withoutGlobalScopes()
            ->where('uuid', $data['stay_id'])
            ->firstOrFail();

        $id = $this->handler->handle(new CreateReservation(
            guestId: $data['guest_id'],
            accountId: (string) $account->uuid,
            stayId: $stay->uuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            adults: (int) ($data['adults'] ?? 1),
            children: (int) ($data['children'] ?? 0),
            babies: (int) ($data['babies'] ?? 0),
            pets: (int) ($data['pets'] ?? 0),
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
