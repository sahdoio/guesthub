<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Facades\DB;
use Modules\Stay\Domain\Repository\StayGuestRepository;

final readonly class EloquentStayGuestRepository implements StayGuestRepository
{
    public function link(string $accountUuid, string $guestUuid): void
    {
        $accountId = DB::table('accounts')
            ->where('uuid', $accountUuid)
            ->value('id');

        if ($accountId === null) {
            return;
        }

        DB::table('account_guests')->insertOrIgnore([
            'account_id' => $accountId,
            'guest_uuid' => $guestUuid,
        ]);
    }

    /** @return list<string> */
    public function guestUuidsForAccount(int $accountId): array
    {
        return DB::table('account_guests')
            ->where('account_id', $accountId)
            ->pluck('guest_uuid')
            ->all();
    }
}
