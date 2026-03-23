<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Http\View;

use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\Stay\Application\Command\CreateReservation;
use Modules\Stay\Application\Command\CreateReservationHandler;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Infrastructure\Persistence\Eloquent\StayModel;

final class ReservationStoreView
{
    public function __construct(
        private CreateReservationHandler $handler,
        private TenantContext $tenantContext,
        private AccountRepository $accountRepository,
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

        $stay = StayModel::query()
            ->withoutGlobalScopes()
            ->where('uuid', $data['stay_id'])
            ->firstOrFail();

        $id = $this->handler->handle(new CreateReservation(
            guestId: $data['guest_id'],
            accountId: (string) $account->uuid,
            stayId: $stay->uuid,
            checkIn: new DateTimeImmutable($data['check_in']),
            checkOut: new DateTimeImmutable($data['check_out']),
        ));

        return redirect("/reservations/{$id}")->with('success', 'Reservation created.');
    }
}
