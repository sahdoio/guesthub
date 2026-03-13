<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class AccountModel extends Model
{
    protected $table = 'accounts';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
        'created_at',
        'updated_at',
    ];
}
