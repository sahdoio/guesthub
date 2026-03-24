<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Integration;

use Modules\IAM\Infrastructure\Integration\UserApi;
use Modules\Stay\Domain\Dto\GuestInfo;
use Modules\Stay\Domain\Service\GuestGateway;

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
