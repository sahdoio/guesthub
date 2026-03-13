<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class RoomModel extends Model
{
    protected $table = 'rooms';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
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
