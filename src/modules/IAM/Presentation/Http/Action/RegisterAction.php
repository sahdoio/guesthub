<?php

declare(strict_types=1);

namespace Modules\IAM\Presentation\Http\Action;

use Modules\Guest\Domain\Repository\GuestRepository;
use Modules\IAM\Application\Command\RegisterActor;
use Modules\IAM\Application\Command\RegisterActorHandler;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Repository\RoleRepository;
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
        private RoleRepository $roleRepository,
        private GuestRepository $guestRepository,
        private InputValidator $validator,
        private JsonResponder $responder,
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
            accountName: $data['name']."'s Account",
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            phone: $data['phone'],
            document: $data['document'],
        ));

        $actor = $this->repository->findByUuid($id);

        $roleNames = array_map(function ($roleId) {
            $role = $this->roleRepository->findById($roleId);

            return $role?->name->value ?? 'unknown';
        }, $actor->roleIds());

        $guestUuid = null;
        if ($actor->subjectType === 'guest' && $actor->subjectId !== null) {
            $guest = $this->guestRepository->findByNumericId($actor->subjectId);
            $guestUuid = $guest ? (string) $guest->uuid : null;
        }

        return $this->responder->created(['data' => ActorPresenter::fromDomain($actor, $roleNames, $guestUuid)]);
    }
}
