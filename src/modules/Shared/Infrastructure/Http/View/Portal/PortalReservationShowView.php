<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Billing\Infrastructure\Persistence\Eloquent\InvoiceModel;
use Modules\Stay\Domain\Exception\ReservationNotFoundException;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Stay\Domain\Service\GuestGateway;
use Modules\Stay\Application\Query\ReservationReadModel;
use Modules\Stay\Domain\StayId;
use Modules\Stay\Domain\Repository\StayRepository;

final class PortalReservationShowView
{
    public function __construct(
        private ReservationRepository $repository,
        private GuestGateway $guestGateway,
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $reservationId = ReservationId::fromString($id);
        $reservation = $this->repository->findByUuidGlobal($reservationId)
            ?? throw ReservationNotFoundException::withId($reservationId);

        // Enforce ownership
        $guestUuid = $request->attributes->get('guest_uuid');
        if ($guestUuid && $reservation->guestId !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        $readModel = ReservationReadModel::fromReservation($reservation);

        $guestInfo = $this->guestGateway->findByUuid($reservation->guestId);
        if ($guestInfo) {
            $readModel = $readModel->withGuest([
                'guest_id' => $guestInfo->guestId,
                'full_name' => $guestInfo->fullName,
                'email' => $guestInfo->email,
                'phone' => $guestInfo->phone,
                'document' => $guestInfo->document,
                'is_vip' => $guestInfo->isVip,
            ]);
        }

        $stay = $this->stayRepository->findByUuid(StayId::fromString($reservation->stayId));
        if ($stay) {
            $readModel = $readModel->withStay([
                'stay_id' => (string) $stay->uuid,
                'name' => $stay->name,
                'address' => $stay->address,
            ]);
        }

        $invoice = InvoiceModel::query()
            ->withoutGlobalScopes()
            ->with(['lineItems', 'payments'])
            ->where('reservation_id', $id)
            ->first();

        return Inertia::render('Portal/Reservations/Show', [
            'reservation' => $readModel,
            'invoice' => $invoice?->toArray(),
            'stripePublishableKey' => config('billing.stripe.publishable_key'),
        ]);
    }
}
