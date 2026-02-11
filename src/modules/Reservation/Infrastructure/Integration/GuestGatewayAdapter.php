<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Integration;

use Illuminate\Support\Facades\DB;
use Modules\Reservation\Domain\Dto\GuestInfo;
use Modules\Reservation\Domain\Service\GuestGateway;

final class GuestGatewayAdapter implements GuestGateway
{
    public function findByUuid(string $guestProfileId): ?GuestInfo
    {
        $record = DB::table('guest_profiles')
            ->where('uuid', $guestProfileId)
            ->first();

        if ($record === null) {
            return null;
        }

        $isVip = in_array($record->loyalty_tier, ['gold', 'platinum'], true);

        return new GuestInfo(
            guestProfileId: $record->uuid,
            fullName: $record->full_name,
            email: $record->email,
            phone: $record->phone,
            document: $record->document,
            isVip: $isVip,
        );
    }
}
