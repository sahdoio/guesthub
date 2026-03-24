<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Listeners;

use Modules\Billing\Infrastructure\IntegrationEvent\InvoiceFullyPaidEvent;
use Modules\Shared\Application\EventDispatcher;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\ValueObject\ReservationStatus;

final readonly class OnInvoiceFullyPaid
{
    public function __construct(
        private ReservationRepository $repository,
        private EventDispatcher $dispatcher,
    ) {}

    public function handle(InvoiceFullyPaidEvent $event): void
    {
        $id = ReservationId::fromString($event->reservationId);
        $reservation = $this->repository->findByUuidGlobal($id);

        if ($reservation === null) {
            return;
        }

        // Only auto-confirm if still pending
        if ($reservation->status !== ReservationStatus::PENDING) {
            return;
        }

        $reservation->confirm();

        $this->repository->save($reservation);

        // Dispatch domain events (ReservationConfirmed)
        foreach ($reservation->pullDomainEvents() as $domainEvent) {
            $this->dispatcher->dispatch($domainEvent);
        }
    }
}
