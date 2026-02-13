<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Integration;

use Modules\Guest\Infrastructure\Integration\GuestProfileApi;
use Modules\Reservation\Domain\Dto\GuestInfo;
use Modules\Reservation\Domain\Service\GuestGateway;

final class GuestGatewayAdapter implements GuestGateway
{
    public function __construct(
        private readonly GuestProfileApi $guestProfileApi,
    ) {}

    public function findByUuid(string $guestProfileId): ?GuestInfo
    {
        $data = $this->guestProfileApi->findByUuid($guestProfileId);

        if ($data === null) {
            return null;
        }

        $isVip = in_array($data->loyaltyTier, ['gold', 'platinum'], true);

        return new GuestInfo(
            guestProfileId: $data->uuid,
            fullName: $data->fullName,
            email: $data->email,
            phone: $data->phone,
            document: $data->document,
            isVip: $isVip,
        );
    }
}
