<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class RoleModel extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
    ];
}
