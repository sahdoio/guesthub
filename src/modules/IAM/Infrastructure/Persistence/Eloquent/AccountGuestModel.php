<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property int $user_id
 * @property string|null $first_reservation_at
 */
final class AccountGuestModel extends Model
{
    protected $table = 'account_guests';

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'user_id',
        'first_reservation_at',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
