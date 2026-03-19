<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Integration;

use Modules\User\Infrastructure\Integration\UserApi;
use Modules\Reservation\Domain\Dto\GuestInfo;
use Modules\Reservation\Domain\Service\GuestGateway;

final class GuestGatewayAdapter implements GuestGateway
{
    public function __construct(
        private readonly UserApi $userApi,
    ) {}

    public function findByUuid(string $guestId): ?GuestInfo
    {
        $data = $this->userApi->findByUuid($guestId);

        if ($data === null) {
            return null;
        }

        $isVip = in_array($data->loyaltyTier, ['gold', 'platinum'], true);

        return new GuestInfo(
            guestId: $data->uuid,
            fullName: $data->fullName,
            email: $data->email,
            phone: $data->phone,
            document: $data->document,
            isVip: $isVip,
        );
    }
}
