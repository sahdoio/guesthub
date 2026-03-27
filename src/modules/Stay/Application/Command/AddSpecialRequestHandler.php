<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use Modules\IAM\Infrastructure\Integration\AccountApi;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\RequestType;
use Modules\Stay\Domain\ValueObject\SpecialRequestId;

final class AddSpecialRequestHandler extends EventDispatchingHandler
{
    public function __construct(
        private ReservationRepository $repository,
        private AccountApi $accountApi,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(AddSpecialRequest $command): SpecialRequestId
    {
        $id = ReservationId::fromString($command->reservationId);
        $reservation = $this->repository->findByUuid($id)
            ?? throw ReservationNotFoundException::withId($id);

        $requestId = $reservation->addSpecialRequest(
            RequestType::from($command->requestType),
            $command->description,
        );

        $this->repository->save($reservation, $this->accountApi->resolveNumericId($reservation->accountId));
        $this->dispatchEvents($reservation);

        return $requestId;
    }
}
