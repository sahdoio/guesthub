<?php

declare(strict_types=1);

namespace Modules\IAM\Application\Command;

use DateTimeImmutable;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Hotel;
use Modules\IAM\Domain\HotelId;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Shared\Application\EventDispatcher;
use Modules\Shared\Application\EventDispatchingHandler;

final readonly class CreateHotelHandler extends EventDispatchingHandler
{
    public function __construct(
        private HotelRepository $repository,
        EventDispatcher $dispatcher,
    ) {
        parent::__construct($dispatcher);
    }

    public function handle(CreateHotel $command): HotelId
    {
        $id = $this->repository->nextIdentity();

        $hotel = Hotel::create(
            uuid: $id,
            accountId: AccountId::fromString($command->accountId),
            name: $command->name,
            slug: $command->slug,
            createdAt: new DateTimeImmutable,
            description: $command->description,
            address: $command->address,
            contactEmail: $command->contactEmail,
            contactPhone: $command->contactPhone,
        );

        $this->repository->save($hotel);
        $this->dispatchEvents($hotel);

        return $id;
    }
}
