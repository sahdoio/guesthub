<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

final class ReservationModel extends Model
{
    use BelongsToTenant;
    protected $table = 'reservations';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_id',
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
