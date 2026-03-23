<?php

declare(strict_types=1);

namespace Modules\Stay\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $stay_id
 * @property string $path
 * @property int $position
 * @property string $created_at
 */
final class StayImageModel extends Model
{
    protected $table = 'stay_images';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'stay_id',
        'path',
        'position',
        'created_at',
    ];

    public function stay(): BelongsTo
    {
        return $this->belongsTo(StayModel::class, 'stay_id', 'id');
    }
}
