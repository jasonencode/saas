<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Contracts\ShouldSettlement;
use App\Models\Plan;
use App\Models\Voucher;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

class VoucherService implements ServiceInterface
{
    /**
     * 创建结算凭据，可选设置计划执行时间（延迟）
     *
     * @param  ShouldSettlement  $settlement
     * @param  string|int|Plan  $plan
     * @param  mixed|null  $scheduledAt
     * @return Voucher
     */
    public function create(ShouldSettlement $settlement, string|int|Plan $plan, mixed $scheduledAt = null): Voucher
    {
        if (is_string($plan)) {
            $plan = Plan::where('alias', $plan)->first();
        } elseif (is_int($plan)) {
            $plan = Plan::find($plan);
        } elseif (!$plan instanceof Plan) {
            throw new InvalidArgumentException('计划参数不合法');
        }

        if (!$plan) {
            throw new InvalidArgumentException('未找到指定的计划');
        }

        if ($plan->isDisabled()) {
            throw new InvalidArgumentException('该计划不可用，请检查计划状态');
        }

        if (!$settlement instanceof Model) {
            throw new InvalidArgumentException('结算目标必须为模型实例');
        }

        $user = $settlement->user;
        if (!$user || !$user->exists) {
            throw new InvalidArgumentException('结算目标未关联有效用户');
        }

        $payload = [
            'plan_id' => $plan->getKey(),
            'user_id' => $user->getKey(),
            'target' => $settlement,
        ];

        if ($scheduledAt !== null) {
            if ($scheduledAt instanceof DateTimeInterface) {
                $scheduled = Carbon::instance($scheduledAt);
            } elseif (is_int($scheduledAt)) {
                $scheduled = now()->addSeconds(max(0, $scheduledAt));
            } else {
                try {
                    $scheduled = Carbon::parse((string) $scheduledAt);
                } catch (Throwable) {
                    throw new InvalidArgumentException('计划执行时间格式不正确');
                }
            }

            if ($scheduled->lessThan(now())) {
                $scheduled = now();
            }

            $payload['scheduled_at'] = $scheduled;
        }

        return Voucher::create($payload);
    }
}
