<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $number
 * @property string $type
 * @property int $floor
 * @property int $capacity
 * @property float $price_per_night
 * @property string $status
 * @property array<string, mixed>|null $amenities
 * @property string $created_at
 * @property string|null $updated_at
 */
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
