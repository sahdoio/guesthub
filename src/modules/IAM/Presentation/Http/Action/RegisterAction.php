<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use Modules\IAM\Application\Command\RegisterActor;
use Modules\IAM\Application\Command\RegisterActorHandler;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Presentation\Http\Presenter\ActorPresenter;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RegisterAction
{
    public function __construct(
        private RegisterActorHandler $handler,
        private ActorRepository $repository,
        private InputValidator $validator,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'document' => ['required', 'string', 'max:50'],
        ]);

        $id = $this->handler->handle(new RegisterActor(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'],
            document: $data['document'],
        ));

        $actor = $this->repository->findByUuid($id);

        return JsonResponder::created(['data' => ActorPresenter::fromDomain($actor)]);
    }
}
