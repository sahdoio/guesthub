<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Stay\Application\Command\CancelReservation;
use Modules\Stay\Application\Command\CancelReservationHandler;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\ReservationId;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final class PortalCancelReservationView
{
    public function __construct(
        private CancelReservationHandler $cancelHandler,
        private ReservationRepository $repository,
        private AccountRepository $accountRepository,
        private TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request, string $id): RedirectResponse
    {
        // Find reservation globally to enforce ownership
        $reservationId = ReservationId::fromString($id);
        $reservation = $this->repository->findByUuidGlobal($reservationId);

        if (! $reservation) {
            abort(404);
        }

        $guestUuid = $request->attributes->get('guest_uuid');
        if ($guestUuid && $reservation->guestId !== $guestUuid) {
            abort(403, 'Access denied.');
        }

        // Set tenant context for the reservation's hotel so save() works
        $account = $this->accountRepository->findByUuid(
            \Modules\IAM\Domain\AccountId::fromString($reservation->accountId)
        );
        if ($account) {
            $numericId = $this->accountRepository->resolveNumericId($account->uuid);
            $this->tenantContext->set($numericId);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10'],
        ]);

        $this->cancelHandler->handle(new CancelReservation(
            reservationId: $id,
            reason: $data['reason'],
        ));

        return redirect("/portal/reservations/{$id}")->with('success', 'Reservation cancelled.');
    }
}
