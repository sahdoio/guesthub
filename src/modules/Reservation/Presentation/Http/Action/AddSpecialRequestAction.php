<?php

declare(strict_types=1);

namespace Modules\Reservation\Presentation\Http\Action;

use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\AddSpecialRequestHandler;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AddSpecialRequestAction
{
    public function __construct(
        private AddSpecialRequestHandler $handler,
        private InputValidator $validator,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $validTypes = implode(',', array_column(RequestType::cases(), 'value'));

        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'type' => ['required', 'string', "in:{$validTypes}"],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $requestId = $this->handler->handle(new AddSpecialRequest(
            reservationId: $request->getAttribute('id'),
            requestType: $data['type'],
            description: $data['description'],
        ));

        return JsonResponder::created([
            'message' => 'Special request added.',
            'request_id' => (string) $requestId,
        ]);
    }
}
