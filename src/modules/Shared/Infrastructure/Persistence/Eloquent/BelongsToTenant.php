<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Modules\Shared\Infrastructure\Persistence\TenantContext;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantContext = app(TenantContext::class);
            if ($tenantContext->isSet()) {
                $builder->where($builder->getModel()->getTable().'.account_id', $tenantContext->id());
            }
        });
    }
}
