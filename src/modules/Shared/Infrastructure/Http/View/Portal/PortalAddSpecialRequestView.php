<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\View\Portal;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\AccountId;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Reservation\Application\Command\AddSpecialRequest;
use Modules\Reservation\Application\Command\AddSpecialRequestHandler;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ReservationId;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

final readonly class PortalAddSpecialRequestView
{
    public function __construct(
        private AddSpecialRequestHandler $addHandler,
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

        // Set tenant context for the reservation's hotel
        $account = $this->accountRepository->findByUuid(AccountId::fromString($reservation->accountId));
        if ($account) {
            $numericId = $this->accountRepository->resolveNumericId($account->uuid);
            $this->tenantContext->set($numericId);
        }

        $data = $request->validate([
            'type' => ['required', 'string', 'in:early_check_in,late_check_out,extra_bed,dietary_restriction,special_occasion,other'],
            'description' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->addHandler->handle(new AddSpecialRequest(
            reservationId: $id,
            requestType: $data['type'],
            description: $data['description'],
        ));

        return redirect("/portal/reservations/{$id}")->with('success', 'Special request added.');
    }
}
