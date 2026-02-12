<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

final class ActorModel extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'actors';

    protected $fillable = [
        'uuid',
        'type',
        'name',
        'email',
        'password',
        'profile_type',
        'profile_id',
    ];

    protected $hidden = [
        'password',
    ];
}
