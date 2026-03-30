<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use DateMalformedStringException;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Application\Command\CreateReservation;
use Modules\Stay\Application\Command\CreateReservationHandler;
use Modules\Stay\Domain\Repository\StayRepository;

final class PortalReservationStoreView
{
    public function __construct(
        private CreateReservationHandler $handler,
        private StayRepository $stayRepository,
        private TenantContext $tenantContext,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'stay_uuid' => ['required', 'string'],
            'adults' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'children' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'babies' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'pets' => ['sometimes', 'integer', 'min:0', 'max:5'],
        ]);

        $stay = $this->stayRepository->findBySlug($data['stay_uuid']);
        if (! $stay) {
            abort(404, 'Stay not found.');
        }

        // Set tenant context for the reservation's stay account
        $this->tenantContext->set((string) $stay->accountId);

        $id = $this->handler->handle(new CreateReservation(
            guestId: $guestUuid,
            accountId: (string) $stay->accountId,
            stayId: (string) $stay->uuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            adults: (int) ($data['adults'] ?? 1),
            children: (int) ($data['children'] ?? 0),
            babies: (int) ($data['babies'] ?? 0),
            pets: (int) ($data['pets'] ?? 0),
        ));

        return redirect("/portal/reservations/{$id}/checkout");
    }
}
