<?php

declare(strict_types=1);

namespace Modules\Billing\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $stripe_event_id
 * @property string $event_type
 * @property string $processed_at
 */
final class StripeWebhookEventModel extends Model
{
    protected $table = 'stripe_webhook_events';

    public $timestamps = false;

    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'processed_at',
    ];
}
