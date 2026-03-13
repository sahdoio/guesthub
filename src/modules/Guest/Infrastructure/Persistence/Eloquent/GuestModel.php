<?php

declare(strict_types=1);

namespace Modules\Guest\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $full_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $document
 * @property string|null $loyalty_tier
 * @property array<string, mixed>|null $preferences
 * @property string $created_at
 * @property string|null $updated_at
 */
final class GuestModel extends Model
{
    use BelongsToTenant;

    protected $table = 'guests';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_id',
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
