<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property string $account_uuid
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property string $type
 * @property string $category
 * @property float|null $price_per_night
 * @property int|null $capacity
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string $status
 * @property array|null $amenities
 * @property string|null $cover_image_path
 * @property string $created_at
 * @property string|null $updated_at
 */
final class StayModel extends Model
{
    use BelongsToTenant;

    protected $table = 'stays';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_uuid',
        'name',
        'slug',
        'description',
        'address',
        'type',
        'category',
        'price_per_night',
        'capacity',
        'contact_email',
        'contact_phone',
        'status',
        'amenities',
        'cover_image_path',
        'created_at',
        'updated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'price_per_night' => 'float',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(StayImageModel::class, 'stay_id', 'id')
            ->orderBy('position');
    }
}
