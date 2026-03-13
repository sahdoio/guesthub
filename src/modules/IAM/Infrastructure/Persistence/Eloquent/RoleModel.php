<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 */
final class RoleModel extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
    ];
}
