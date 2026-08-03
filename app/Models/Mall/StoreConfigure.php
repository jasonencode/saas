<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasRegion;
use App\Policies\Mall\StoreConfigurePolicy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[Table(key: 'tenant_id')]
#[UsePolicy(StoreConfigurePolicy::class)]
#[WithoutIncrementing]
class StoreConfigure extends Model
{
    use BelongsToTenant,
        HasCovers,
        HasRegion;

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * 默认物流公司
     *
     * @return BelongsTo<Express>
     */
    public function defaultExpress(): BelongsTo
    {
        return $this->belongsTo(Express::class, 'default_express_id')
            ->withoutGlobalScopes();
    }

    /**
     * 商城是否已开通
     *
     * @return bool 是否开通
     */
    public function isOpened(): bool
    {
        return (bool) $this->enabled;
    }
}
