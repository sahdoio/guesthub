<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Query\GetReservation;
use Modules\Reservation\Application\Query\GetReservationHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ShowReservationAction
{
    public function __construct(
        private GetReservationHandler $handler,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $readModel = $this->handler->handle(
            new GetReservation($request->getAttribute('id')),
        );

        return JsonResponder::ok(['data' => $readModel]);
    }
}
