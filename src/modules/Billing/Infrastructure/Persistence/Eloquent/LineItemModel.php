<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $invoice_id
 * @property string $description
 * @property int $unit_price_cents
 * @property int $quantity
 * @property int $total_cents
 * @property string $created_at
 */
final class LineItemModel extends Model
{
    protected $table = 'invoice_line_items';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'invoice_id',
        'description',
        'unit_price_cents',
        'quantity',
        'total_cents',
        'created_at',
    ];

    /** @return BelongsTo<InvoiceModel, self> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }
}
