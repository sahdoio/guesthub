<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Listeners;

use Modules\IAM\Application\Command\ProvisionActorAccount;
use Modules\IAM\Application\Command\ProvisionActorAccountHandler;
use Modules\IAM\Domain\Event\UserCreated;

final readonly class OnUserCreated
{
    public function __construct(
        private ProvisionActorAccountHandler $handler,
    ) {}

    public function handle(UserCreated $event): void
    {
        $this->handler->handle(new ProvisionActorAccount(
            userId: (string) $event->userId,
            name: $event->name,
            email: $event->email,
            hashedPassword: $event->hashedPassword,
            actorType: $event->actorType,
            accountName: $event->accountName,
            accountSlug: $event->accountSlug,
        ));
    }
}
