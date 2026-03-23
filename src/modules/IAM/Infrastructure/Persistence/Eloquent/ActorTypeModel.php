<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 */
final class ActorTypeModel extends Model
{
    protected $table = 'actor_types';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
    ];
}
