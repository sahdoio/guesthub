<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Application\Command\CreateReservation;
use Modules\Stay\Application\Command\CreateReservationHandler;
use Modules\Stay\Domain\Repository\StayRepository;
use Modules\Stay\Domain\StayId;

final class ReservationStoreView
{
    public function __construct(
        private CreateReservationHandler $handler,
        private TenantContext $tenantContext,
        private AccountRepository $accountRepository,
        private StayRepository $stayRepository,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['required', 'uuid'],
            'stay_id' => ['required', 'uuid'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        $account = $this->accountRepository->findByNumericId($this->tenantContext->id());

        $stay = $this->stayRepository->findByUuid(StayId::fromString($data['stay_id']));
        abort_if($stay === null, 404, 'Stay not found.');

        $id = $this->handler->handle(new CreateReservation(
            guestId: $data['guest_id'],
            accountId: (string) $account->uuid,
            stayId: (string) $stay->uuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
        ));

        return redirect("/reservations/{$id}")->with('success', 'Reservation created.');
    }
}
