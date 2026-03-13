<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

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
        'subject_type',
        'subject_id',
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'actor_roles', 'actor_id', 'role_id');
    }
}
