<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property string $account_uuid
 * @property int $stay_id
 * @property string $stay_uuid
 * @property string $status
 * @property string $guest_id
 * @property string $check_in
 * @property string $check_out
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
        'account_uuid',
        'stay_id',
        'stay_uuid',
        'status',
        'guest_id',
        'check_in',
        'check_out',
        'adults',
        'children',
        'babies',
        'pets',
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

    public function stay(): BelongsTo
    {
        return $this->belongsTo(StayModel::class, 'stay_id');
    }
}
