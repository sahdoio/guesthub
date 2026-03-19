<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $account_uuid
 * @property int $hotel_id
 * @property string $hotel_uuid
 * @property string $status
 * @property string $guest_id
 * @property string $check_in
 * @property string $check_out
 * @property string $room_type
 * @property string|null $assigned_room_number
 * @property array<int, array<string, mixed>>|null $special_requests
 * @property string|null $cancellation_reason
 * @property string $created_at
 * @property string|null $updated_at
 * @property string|null $confirmed_at
 * @property string|null $checked_in_at
 * @property string|null $checked_out_at
 * @property string|null $cancelled_at
 */
final class ReservationModel extends Model
{
    use BelongsToTenant;

    protected $table = 'reservations';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_id',
        'account_uuid',
        'hotel_id',
        'hotel_uuid',
        'status',
        'guest_id',
        'check_in',
        'check_out',
        'room_type',
        'assigned_room_number',
        'special_requests',
        'cancellation_reason',
        'created_at',
        'updated_at',
        'confirmed_at',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'special_requests' => 'array',
        ];
    }
}
