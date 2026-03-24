<?php

declare(strict_types=1);

namespace Modules\Stay\Application\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Application\EventDispatcher;
use Modules\Stay\Domain\Event\ReservationCreated;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Infrastructure\IntegrationEvent\ReservationCreatedEvent;

final readonly class OnReservationCreated
{
    public function __construct(
        private ReservationRepository $repository,
        private EventDispatcher $dispatcher,
        private GuestGateway $guestGateway,
    ) {}

    public function handle(ReservationCreated $event): void
    {
        // Record in stay_guests table
        $record = DB::table('reservations')
            ->where('uuid', (string) $event->reservationId)
            ->first(['account_id', 'guest_id', 'created_at']);

        if ($record !== null) {
            DB::table('stay_guests')->insertOrIgnore([
                'account_id' => $record->account_id,
                'guest_uuid' => $record->guest_id,
                'first_reservation_at' => $record->created_at,
            ]);
        }

        // Dispatch integration event for Billing module
        $reservation = $this->repository->findByUuid($event->reservationId)
            ?? throw ReservationNotFoundException::withId($event->reservationId);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestId);

        $this->dispatcher->dispatch(new ReservationCreatedEvent(
            reservationId: (string) $event->reservationId,
            guestEmail: $guestInfo->email ?? '',
            stayId: $reservation->stayId,
            checkIn: $reservation->period->checkIn->format('Y-m-d'),
            checkOut: $reservation->period->checkOut->format('Y-m-d'),
            isVip: $guestInfo->isVip ?? false,
            occurredAt: $event->occurredOn(),
        ));
    }
}
