<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasRegion;
use App\Policies\StoreConfigurePolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 店铺配置模型
 */
#[Unguarded]
#[Table(key: 'tenant_id')]
#[UsePolicy(StoreConfigurePolicy::class)]
#[WithoutIncrementing]
class StoreConfigure extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasRegion;

    /**
     * 默认物流公司
     */
    public function defaultExpress(): BelongsTo
    {
        return $this->belongsTo(Express::class, 'default_express_id')
            ->withoutGlobalScopes();
    }
}
