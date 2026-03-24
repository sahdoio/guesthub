<?php

declare(strict_types=1);

namespace Modules\Stay\Presentation\Http\Action;

use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Stay\Application\Command\ConfirmReservation;
use Modules\Stay\Application\Command\ConfirmReservationHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConfirmReservationAction
{
    public function __construct(
        private ConfirmReservationHandler $handler,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->handler->handle(new ConfirmReservation(
            reservationId: $request->getAttribute('id'),
        ));

        return $this->responder->ok(['message' => 'Reservation confirmed.']);
    }
}
