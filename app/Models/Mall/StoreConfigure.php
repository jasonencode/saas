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

    /**
     * 开通商城（置 enabled=true）
     *
     * @return bool 是否保存成功
     */
    public function open(): bool
    {
        $this->enabled = true;

        return $this->save();
    }

    /**
     * 关闭商城（置 enabled=false）
     *
     * @return bool 是否保存成功
     */
    public function close(): bool
    {
        $this->enabled = false;

        return $this->save();
    }

    /**
     * 获取指定租户的店铺配置（不存在时返回 null）
     *
     * @param  int  $tenantId  租户 ID
     *
     * @return StoreConfigure|null 店铺配置实例
     */
    public static function ofTenant(int $tenantId): ?StoreConfigure
    {
        return static::where('tenant_id', $tenantId)->first();
    }

    /**
     * 判断指定租户的商城是否已开通
     *
     * @param  int  $tenantId  租户 ID
     *
     * @return bool 是否已开通
     */
    public static function isTenantOpened(int $tenantId): bool
    {
        return (bool) static::where('tenant_id', $tenantId)->where('enabled', true)->exists();
    }
}
