<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 */
final class TypeModel extends Model
{
    protected $table = 'types';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
    ];
}
