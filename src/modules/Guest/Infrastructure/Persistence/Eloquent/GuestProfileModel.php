<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class GuestProfileModel extends Model
{
    protected $table = 'guest_profiles';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'full_name',
        'email',
        'phone',
        'document',
        'loyalty_tier',
        'preferences',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
        ];
    }
}
