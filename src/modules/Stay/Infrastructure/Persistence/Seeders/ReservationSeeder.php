<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\IAM\Domain\Repository\AccountRepository;
use Modules\IAM\Domain\ValueObject\AccountId;
use Modules\IAM\Infrastructure\Persistence\Seeders\AccountSeeder;
use Modules\IAM\Infrastructure\Persistence\Seeders\UserSeeder;
use Modules\Shared\Infrastructure\Persistence\TenantContext;
use Modules\Stay\Domain\Repository\ReservationRepository;
use Modules\Stay\Domain\Reservation;
use Modules\Stay\Domain\ValueObject\RequestType;
use Modules\Stay\Domain\ValueObject\ReservationPeriod;

class ReservationSeeder extends Seeder
{
    public function __construct(
        private readonly ReservationRepository $repository,
        private readonly AccountRepository $accountRepository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function run(): void
    {
        $accountUuid = AccountSeeder::$defaultAccountUuid;
        $accountId = $this->accountRepository->resolveNumericId(AccountId::fromString($accountUuid));
        $this->tenantContext->set($accountId);

        $stayUuid = StaySeeder::$defaultStayUuid;
        $userIds = UserSeeder::$userIds;

        // 1. Pending reservation (future, regular guest) — couple
        $r1 = Reservation::create(
            $this->repository->nextIdentity(),
            $userIds['alice@example.com'],
            $accountUuid,
            $stayUuid,
            new ReservationPeriod(new DateTimeImmutable('+3 days'), new DateTimeImmutable('+6 days')),
            adults: 2,
        );
        $r1->addSpecialRequest(RequestType::EARLY_CHECK_IN, 'Arriving on early morning flight');
        $this->save($r1);

        // 2. Confirmed reservation (future, VIP guest) — couple with pet
        $r2 = Reservation::create(
            $this->repository->nextIdentity(),
            $userIds['bob.vip@example.com'],
            $accountUuid,
            $stayUuid,
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+5 days')),
            adults: 2,
            pets: 1,
        );
        $r2->addSpecialRequest(RequestType::SPECIAL_OCCASION, 'Anniversary celebration - champagne and flowers');
        $r2->addSpecialRequest(RequestType::LATE_CHECK_OUT, 'Late flight, need room until 4pm');
        $r2->confirm();
        $this->save($r2);

        // 3. Checked-in reservation (current stay) — family with child and baby
        $r3 = Reservation::create(
            $this->repository->nextIdentity(),
            $userIds['carol@example.com'],
            $accountUuid,
            $stayUuid,
            new ReservationPeriod(new DateTimeImmutable('today'), new DateTimeImmutable('+3 days')),
            adults: 2,
            children: 1,
            babies: 1,
        );
        $r3->addSpecialRequest(RequestType::EXTRA_BED, 'Extra bed for child');
        $r3->addSpecialRequest(RequestType::DIETARY_RESTRICTION, 'Guest is vegetarian - breakfast buffet');
        $r3->confirm();
        $r3->checkIn();
        $r3->fulfillSpecialRequest($r3->specialRequests[0]->id());
        $this->save($r3);

        // 4. Cancelled reservation (VIP guest) — solo traveler
        $r4 = Reservation::create(
            $this->repository->nextIdentity(),
            $userIds['david.m@example.com'],
            $accountUuid,
            $stayUuid,
            new ReservationPeriod(new DateTimeImmutable('+10 days'), new DateTimeImmutable('+14 days')),
            adults: 1,
        );
        $r4->cancel('Business trip rescheduled to next month');
        $this->save($r4);

        // 5. Confirmed reservation with multiple special requests — group with children
        $r5 = Reservation::create(
            $this->repository->nextIdentity(),
            $userIds['eva.t@example.com'],
            $accountUuid,
            $stayUuid,
            new ReservationPeriod(new DateTimeImmutable('+7 days'), new DateTimeImmutable('+10 days')),
            adults: 2,
            children: 2,
        );
        $r5->addSpecialRequest(RequestType::EARLY_CHECK_IN, 'Need room ready by noon');
        $r5->addSpecialRequest(RequestType::DIETARY_RESTRICTION, 'Gluten-free meals required');
        $r5->addSpecialRequest(RequestType::OTHER, 'Extra pillows and blankets please');
        $r5->confirm();
        $this->save($r5);
    }

    private function save(Reservation $reservation): void
    {
        $reservation->pullDomainEvents(); // Discard events during seeding
        $this->repository->save($reservation, $this->tenantContext->id());

        // Manually insert account_guests since domain events are discarded
        DB::table('account_guests')->insertOrIgnore([
            'account_id' => $this->tenantContext->id(),
            'guest_uuid' => $reservation->guestId,
        ]);
    }
}
