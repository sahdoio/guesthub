<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Command\ConfirmReservation;
use Modules\Reservation\Application\Command\ConfirmReservationHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConfirmReservationAction
{
    public function __construct(
        private ConfirmReservationHandler $handler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->handler->handle(new ConfirmReservation(
            reservationId: $request->getAttribute('id'),
        ));

        return JsonResponder::ok(['message' => 'Reservation confirmed.']);
    }
}
