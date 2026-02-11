<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Modules\Guest\Infrastructure\Persistence\Seeders\GuestSeeder;
use Modules\Reservation\Domain\Reservation;
use Modules\Reservation\Domain\Repository\ReservationRepository;
use Modules\Reservation\Domain\ValueObject\RequestType;
use Modules\Reservation\Domain\ValueObject\ReservationPeriod;

class ReservationSeeder extends Seeder
{
    public function __construct(
        private readonly ReservationRepository $repository,
    ) {}

    public function run(): void
    {
        $guestIds = GuestSeeder::$guestIds;

        // 1. Pending reservation (future, regular guest)
        $r1 = Reservation::create(
            $this->repository->nextIdentity(),
            $guestIds['alice@example.com'],
            new ReservationPeriod(new DateTimeImmutable('+3 days'), new DateTimeImmutable('+6 days')),
            'SINGLE',
        );
        $r1->addSpecialRequest(RequestType::EARLY_CHECK_IN, 'Arriving on early morning flight');
        $this->save($r1);

        // 2. Confirmed reservation (future, VIP guest)
        $r2 = Reservation::create(
            $this->repository->nextIdentity(),
            $guestIds['bob.vip@example.com'],
            new ReservationPeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('+5 days')),
            'SUITE',
        );
        $r2->addSpecialRequest(RequestType::SPECIAL_OCCASION, 'Anniversary celebration - champagne and flowers');
        $r2->addSpecialRequest(RequestType::LATE_CHECK_OUT, 'Late flight, need room until 4pm');
        $r2->confirm();
        $this->save($r2);

        // 3. Checked-in reservation (current stay, regular guest)
        $r3 = Reservation::create(
            $this->repository->nextIdentity(),
            $guestIds['carol@example.com'],
            new ReservationPeriod(new DateTimeImmutable('today'), new DateTimeImmutable('+3 days')),
            'DOUBLE',
        );
        $r3->addSpecialRequest(RequestType::EXTRA_BED, 'Extra bed for child');
        $r3->addSpecialRequest(RequestType::DIETARY_RESTRICTION, 'Guest is vegetarian - breakfast buffet');
        $r3->confirm();
        $r3->checkIn('305');
        $r3->fulfillSpecialRequest($r3->specialRequests[0]->id());
        $this->save($r3);

        // 4. Cancelled reservation (VIP guest)
        $r4 = Reservation::create(
            $this->repository->nextIdentity(),
            $guestIds['david.m@example.com'],
            new ReservationPeriod(new DateTimeImmutable('+10 days'), new DateTimeImmutable('+14 days')),
            'SUITE',
        );
        $r4->cancel('Business trip rescheduled to next month');
        $this->save($r4);

        // 5. Confirmed reservation with multiple special requests (future, regular)
        $r5 = Reservation::create(
            $this->repository->nextIdentity(),
            $guestIds['eva.t@example.com'],
            new ReservationPeriod(new DateTimeImmutable('+7 days'), new DateTimeImmutable('+10 days')),
            'DOUBLE',
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
        $this->repository->save($reservation);
    }
}
