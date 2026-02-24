<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use Modules\IAM\Application\Command\AuthenticateActor;
use Modules\IAM\Application\Command\AuthenticateActorHandler;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LoginAction
{
    public function __construct(
        private AuthenticateActorHandler $handler,
        private InputValidator $validator,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->handler->handle(new AuthenticateActor(
            email: $data['email'],
            password: $data['password'],
        ));

        return JsonResponder::ok([
            'token' => $result['token'],
            'actor_id' => $result['actor_id'],
        ]);
    }
}
