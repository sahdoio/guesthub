<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use JsonException;
use Modules\IAM\Application\Command\RegisterUser;
use Modules\IAM\Application\Command\RegisterUserHandler;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\TypeRepository;
use Modules\IAM\Presentation\Http\Presenter\ActorPresenter;
use Modules\Shared\Presentation\Http\JsonResponder;
use Modules\Shared\Presentation\Validation\InputValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RegisterAction
{
    public function __construct(
        private RegisterUserHandler $handler,
        private ActorRepository $actorRepository,
        private TypeRepository $typeRepository,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    /**
     * @throws JsonException
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'regex:/^[1-9]\d{6,14}$/'],
            'document' => ['required', 'string', 'max:50'],
        ]);

        $userId = $this->handler->handle(new RegisterUser(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'],
            document: $data['document'],
        ));

        // Actor was created synchronously via UserCreated event listener
        $actor = $this->actorRepository->findByEmail($data['email']);

        $typeNames = array_map(function ($typeId) {
            $type = $this->typeRepository->findById($typeId);

            return $type?->name->value ?? 'unknown';
        }, $actor->typeIds());

        return $this->responder->created(['data' => ActorPresenter::fromDomain($actor, $typeNames, (string) $userId)]);
    }
}
