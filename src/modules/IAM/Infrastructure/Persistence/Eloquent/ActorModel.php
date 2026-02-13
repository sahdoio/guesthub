<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

final class ActorModel extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'actors';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'type',
        'name',
        'email',
        'password',
        'profile_type',
        'profile_id',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
    ];
}
