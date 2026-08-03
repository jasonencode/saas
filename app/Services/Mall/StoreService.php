<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\ApplyStatus;
use App\Models\Mall\ReturnAddress;
use App\Models\Mall\StoreApply;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Tenant;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreService implements ServiceInterface
{
    /**
     * 开通商城
     *
     * 为指定租户创建或激活店铺配置，开启商城功能。
     *
     * @param  Tenant  $tenant  租户
     * @param  array  $config  店铺配置（store_name, auto_complete_days, order_expired_minutes 等）
     *
     * @throws Throwable 事务异常
     *
     * @return StoreConfigure 店铺配置
     */
    public function openStore(Tenant $tenant, array $config = []): StoreConfigure
    {
        return DB::transaction(static function () use ($tenant, $config): StoreConfigure {
            return StoreConfigure::updateOrCreate(
                ['tenant_id' => $tenant->getKey()],
                array_merge(['enabled' => true], $config),
            );
        });
    }

    /**
     * 关闭商城
     *
     * 关闭指定租户的商城功能。
     *
     * @param  Tenant  $tenant  租户
     *
     * @throws Throwable 事务异常
     *
     * @return StoreConfigure 店铺配置
     */
    public function closeStore(Tenant $tenant): StoreConfigure
    {
        return DB::transaction(static function () use ($tenant): StoreConfigure {
            return StoreConfigure::updateOrCreate(
                ['tenant_id' => $tenant->getKey()],
                ['enabled' => false],
            );
        });
    }

    /**
     * 设置默认退货地址
     *
     * @throws Throwable
     */
    public function setDefaultReturnAddress(ReturnAddress $address): void
    {
        DB::transaction(static function () use ($address) {
            ReturnAddress::where('tenant_id', $address->tenant_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $address->is_default = true;
            $address->save();
        });
    }

    /**
     * 审核店铺申请
     *
     * 审核通过时同步创建/激活该租户的店铺配置，作为商城是否已开通的总开关。
     *
     * @param  StoreApply  $apply  申请记录
     * @param  ApplyStatus|string  $status  审核状态
     * @param  string|null  $reason  审核原因/备注
     * @param  Authenticatable|null  $approver  审核人
     *
     * @throws Throwable 事务异常
     */
    public function auditApply(StoreApply $apply, ApplyStatus|string $status, ?string $reason = null, ?Authenticatable $approver = null): void
    {
        $approver = $approver ?: Filament::auth()->user();

        $apply->status = $status;
        if ($status === ApplyStatus::Rejected->value || $status === ApplyStatus::Rejected) {
            $apply->reason = $reason;
        } else {
            $apply->remark = $reason;
        }

        if ($approver) {
            $apply->approver_type = $approver->getMorphClass();
            $apply->approver_id = $approver->getKey();
        }

        DB::transaction(static function () use ($apply, $status): void {
            $apply->save();

            // 审核通过：创建或激活店铺配置，置 enabled=true 作为商城开通总开关
            if ($status === ApplyStatus::Approved || $status === ApplyStatus::Approved->value) {
                StoreConfigure::updateOrCreate(
                    ['tenant_id' => $apply->tenant_id],
                    ['enabled' => true],
                );
            }
        });
    }
}
