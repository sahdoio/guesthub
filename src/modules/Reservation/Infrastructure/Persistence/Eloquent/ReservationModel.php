<?php

declare(strict_types=1);

namespace Modules\Reservation\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class ReservationModel extends Model
{
    protected $table = 'reservations';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'status',
        'guest_profile_id',
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
