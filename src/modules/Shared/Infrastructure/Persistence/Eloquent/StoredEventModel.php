<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class StoredEventModel extends Model
{
    protected $table = 'stored_events';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_on' => 'datetime',
            'stored_at' => 'datetime',
        ];
    }
}
