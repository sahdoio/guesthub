<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $account_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int|null $user_id
 * @property string $created_at
 * @property string|null $updated_at
 */
final class ActorModel extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'actors';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_id',
        'name',
        'email',
        'password',
        'user_id',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(ActorTypeModel::class, 'actor_type_pivot', 'actor_id', 'type_id');
    }
}
