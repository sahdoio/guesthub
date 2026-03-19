<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use Modules\User\Domain\Repository\UserRepository;
use Modules\IAM\Application\Command\RegisterActor;
use Modules\IAM\Application\Command\RegisterActorHandler;
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
        private RegisterActorHandler $handler,
        private ActorRepository $repository,
        private TypeRepository $typeRepository,
        private UserRepository $userRepository,
        private InputValidator $validator,
        private JsonResponder $responder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->validator->validate((array) $request->getParsedBody(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'regex:/^[1-9]\d{6,14}$/'],
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

        $typeNames = array_map(function ($typeId) {
            $type = $this->typeRepository->findById($typeId);

            return $type?->name->value ?? 'unknown';
        }, $actor->typeIds());

        $userUuid = null;
        if ($actor->userId !== null) {
            $user = $this->userRepository->findByNumericId($actor->userId);
            $userUuid = $user ? (string) $user->uuid : null;
        }

        return $this->responder->created(['data' => ActorPresenter::fromDomain($actor, $typeNames, $userUuid)]);
    }
}
