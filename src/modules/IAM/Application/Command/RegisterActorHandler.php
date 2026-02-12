<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Modules\IAM\Domain\Actor;
use Modules\IAM\Domain\ActorId;
use Modules\IAM\Domain\Exception\ActorAlreadyExistsException;
use Modules\IAM\Domain\Repository\ActorRepository;
use Modules\IAM\Domain\Service\GuestProfileGateway;
use Modules\IAM\Domain\Service\PasswordHasher;
use Modules\IAM\Domain\ValueObject\ActorType;

final readonly class RegisterActorHandler
{
    public function __construct(
        private ActorRepository $repository,
        private PasswordHasher $hasher,
        private GuestProfileGateway $guestProfileGateway,
    ) {}

    public function handle(RegisterActor $command): ActorId
    {
        $existing = $this->repository->findByEmail($command->email);

        if ($existing !== null) {
            throw ActorAlreadyExistsException::withEmail($command->email);
        }

        $guestProfileId = $this->guestProfileGateway->create(
            name: $command->name,
            email: $command->email,
            phone: $command->phone,
            document: $command->document,
        );

        $id = $this->repository->nextIdentity();

        $actor = Actor::register(
            uuid: $id,
            type: ActorType::GUEST,
            name: $command->name,
            email: $command->email,
            password: $this->hasher->hash($command->password),
            profileType: ActorType::GUEST->value,
            profileId: $guestProfileId,
            createdAt: new DateTimeImmutable(),
        );

        $this->repository->save($actor);

        return $id;
    }
}
