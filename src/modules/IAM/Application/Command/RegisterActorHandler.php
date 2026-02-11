<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Exception\ActorAlreadyExistsException;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\ActorType;

final class RegisterActorHandler
{
    public function __construct(
        private readonly ActorRepository $repository,
        private readonly PasswordHasher $hasher,
    ) {}

    public function handle(RegisterActor $command): ActorId
    {
        $existing = $this->repository->findByEmail($command->email);

        if ($existing !== null) {
            throw ActorAlreadyExistsException::withEmail($command->email);
        }

        $id = $this->repository->nextIdentity();

        $actor = Actor::register(
            uuid: $id,
            type: ActorType::from($command->type),
            name: $command->name,
            email: $command->email,
            password: $this->hasher->hash($command->password),
            guestProfileId: $command->guestProfileId,
            createdAt: new DateTimeImmutable(),
        );

        $this->repository->save($actor);

        return $id;
    }
}
