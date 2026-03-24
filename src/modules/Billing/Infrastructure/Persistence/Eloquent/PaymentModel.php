<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $invoice_id
 * @property int $amount_cents
 * @property string $currency
 * @property string $status
 * @property string $method
 * @property string|null $stripe_payment_intent_id
 * @property string|null $failure_reason
 * @property string $created_at
 * @property string|null $succeeded_at
 * @property string|null $failed_at
 */
final class PaymentModel extends Model
{
    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'invoice_id',
        'amount_cents',
        'currency',
        'status',
        'method',
        'stripe_payment_intent_id',
        'failure_reason',
        'created_at',
        'succeeded_at',
        'failed_at',
    ];

    /** @return BelongsTo<InvoiceModel, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'invoice_id');
    }
}
