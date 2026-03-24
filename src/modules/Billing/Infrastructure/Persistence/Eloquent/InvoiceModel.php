<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IAM\Infrastructure\Persistence\Eloquent\AccountModel;
use Modules\IAM\Infrastructure\Persistence\Eloquent\UserModel;
use Modules\Shared\Infrastructure\Persistence\Eloquent\BelongsToTenant;
use Modules\Stay\Infrastructure\Persistence\Eloquent\ReservationModel;

/**
 * @property int $id
 * @property string $uuid
 * @property int $account_id
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
        'account_id',
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

    /** @return HasMany<LineItemModel> */
    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItemModel::class, 'invoice_id');
    }

    /** @return HasMany<PaymentModel> */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentModel::class, 'invoice_id');
    }

    /** @return BelongsTo<AccountModel, self> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    /** @return BelongsTo<ReservationModel, self> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(ReservationModel::class, 'reservation_id', 'uuid');
    }

    /** @return BelongsTo<UserModel, self> */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'guest_id', 'uuid');
    }
}
