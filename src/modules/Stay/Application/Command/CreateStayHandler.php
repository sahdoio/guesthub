<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Command;

use DateTimeImmutable;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\Stay;
use Modules\Stay\Domain\StayId;

final class CreateStayHandler extends EventDispatchingHandler
{
    public function __construct(
        private StayRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CreateStay $command): StayId
    {
        $id = $this->repository->nextIdentity();

        $stay = Stay::create(
            uuid: $id,
            accountId: $command->accountId,
            name: $command->name,
            slug: $command->slug,
            type: $command->type,
            category: $command->category,
            pricePerNight: $command->pricePerNight,
            capacity: $command->capacity,
            createdAt: new DateTimeImmutable,
            description: $command->description,
            address: $command->address,
            contactEmail: $command->contactEmail,
            contactPhone: $command->contactPhone,
            amenities: $command->amenities,
        );

        $this->repository->save($stay);
        $this->dispatchEvents($stay);

        return $id;
    }
}
