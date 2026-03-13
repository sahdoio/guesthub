<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

final class RoomModel extends Model
{
    use BelongsToTenant;
    protected $table = 'rooms';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_id',
        'number',
        'type',
        'floor',
        'capacity',
        'price_per_night',
        'status',
        'amenities',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'price_per_night' => 'float',
        ];
    }
}
