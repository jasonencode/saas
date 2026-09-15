<?php

namespace App\Console\Commands\Campaign;

use App\Console\Commands\BaseCommand;
use App\Models\Campaign\CouponUser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('app:campaign:coupon-expire')]
#[Description('自动清理用户已过期的优惠券')]
class CouponExpireCommand extends BaseCommand
{
    public function getCommandLabel(): string
    {
        return '优惠券过期清理';
    }

    public function handle(): int
    {
        $this->info('开始执行过期优惠券清理...');

        $count = 0;

        // 仅清理「未使用且已过期」的券实例，已核销记录保留（coupon_order 依赖）
        CouponUser::where('is_used', false)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->chunkById(500, function ($couponUsers) use (&$count) {
                $ids = $couponUsers->pluck('id')->all();
                $count += count($ids);

                CouponUser::whereIn('id', $ids)->delete();
            });

        $this->info("任务执行完毕，共清理 $count 张过期优惠券。");

        $this->logContext(['completed' => $count]);

        return self::SUCCESS;
    }
}
