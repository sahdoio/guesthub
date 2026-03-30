<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;

/**
 * @property int $id
 * @property string $uuid
 * @property string $account_uuid
 * @property string $reservation_id
 * @property string $guest_id
 * @property string $status
 * @property int $subtotal_cents
 * @property int $tax_cents
 * @property int $total_cents
 * @property string $currency
 * @property string|null $stripe_customer_id
 * @property string|null $notes
 * @property string $created_at
 * @property string|null $issued_at
 * @property string|null $paid_at
 * @property string|null $voided_at
 * @property string|null $refunded_at
 */
final class InvoiceModel extends Model
{
    use BelongsToTenant;

    protected $table = 'invoices';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'account_uuid',
        'reservation_id',
        'guest_id',
        'status',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'currency',
        'stripe_customer_id',
        'notes',
        'created_at',
        'issued_at',
        'paid_at',
        'voided_at',
        'refunded_at',
    ];

    /** @return HasMany<LineItemModel, $this> */
    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItemModel::class, 'invoice_id');
    }

    /** @return HasMany<PaymentModel, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentModel::class, 'invoice_id');
    }
}
