<?php

declare(strict_types=1);

namespace Modules\IAM\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string $status
 * @property string $created_at
 * @property string|null $updated_at
 */
final class HotelModel extends Model
{
    use BelongsToTenant;

    protected $table = 'hotels';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_id',
        'name',
        'slug',
        'description',
        'address',
        'contact_email',
        'contact_phone',
        'status',
        'created_at',
        'updated_at',
    ];
}
