<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\CreateReservationHandler;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class PortalReservationStoreView
{
    public function __construct(
        private CreateReservationHandler $handler,
        private HotelRepository $hotelRepository,
        private AccountRepository $accountRepository,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $guestUuid = $request->attributes->get('guest_uuid');

        $data = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
            'hotel_slug' => ['required', 'string'],
        ]);

        $hotel = $this->hotelRepository->findBySlug($data['hotel_slug']);
        if (! $hotel) {
            abort(404, 'Hotel not found.');
        }

        // Set tenant context for the reservation's hotel account
        $numericId = $this->accountRepository->resolveNumericId($hotel->accountId);
        $this->tenantContext->set($numericId);

        $id = $this->handler->handle(new CreateReservation(
            guestId: $guestUuid,
            accountId: (string) $hotel->accountId,
            hotelId: (string) $hotel->uuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            roomType: $data['room_type'],
        ));

        return redirect("/portal/reservations/{$id}")->with('success', 'Reservation created.');
    }
}
