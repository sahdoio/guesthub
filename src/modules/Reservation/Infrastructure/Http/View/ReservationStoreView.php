<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Http\View;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\Repository\HotelRepository;
use Modules\Reservation\Application\Command\CreateReservation;
use Modules\Reservation\Application\Command\CreateReservationHandler;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class ReservationStoreView
{
    public function __construct(
        private CreateReservationHandler $handler,
        private TenantContext $tenantContext,
        private AccountRepository $accountRepository,
        private HotelRepository $hotelRepository,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type' => ['required', 'string', 'in:SINGLE,DOUBLE,SUITE'],
        ]);

        $account = $this->accountRepository->findByNumericId($this->tenantContext->id());
        $hotels = $this->hotelRepository->findByAccountId($account->uuid);
        $hotel = $hotels[0] ?? throw new \DomainException('No hotel found for this account.');

        $id = $this->handler->handle(new CreateReservation(
            guestId: $data['guest_id'],
            accountId: (string) $account->uuid,
            hotelId: (string) $hotel->uuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
            roomType: $data['room_type'],
        ));

        return redirect("/reservations/{$id}")->with('success', 'Reservation created.');
    }
}
