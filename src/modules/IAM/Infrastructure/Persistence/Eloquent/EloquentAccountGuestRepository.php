<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Modules\IAM\Domain\Repository\AccountGuestRepository;

final readonly class EloquentAccountGuestRepository implements AccountGuestRepository
{
    public function link(string $accountUuid, string $guestUuid): void
    {
        $accountId = AccountModel::where('uuid', $accountUuid)->value('id');
        $userId = UserModel::where('uuid', $guestUuid)->value('id');

        if ($accountId === null || $userId === null) {
            return;
        }

        AccountGuestModel::insertOrIgnore([
            'account_id' => $accountId,
            'user_id' => $userId,
        ]);
    }

    /** @return list<string> */
    public function guestUuidsForAccount(string $accountUuid): array
    {
        $accountId = AccountModel::where('uuid', $accountUuid)->value('id');

        if ($accountId === null) {
            return [];
        }

        return AccountGuestModel::where('account_id', $accountId)
            ->join('users', 'users.id', '=', 'account_guests.user_id')
            ->pluck('users.uuid')
            ->all();
    }
}
