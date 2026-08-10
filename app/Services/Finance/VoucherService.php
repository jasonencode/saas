<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Contracts\ShouldSettlement;
use App\Jobs\Finance\VoucherAutoRunJob;
use App\Models\Finance\Plan;
use App\Models\Finance\Voucher;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

class VoucherService implements ServiceInterface
{
    /**
     * 创建结算凭据
     *
     * @param  ShouldSettlement  $settlement  结算目标
     * @param  Plan  $plan  结算计划
     * @param  mixed  $scheduledAt  计划执行时间（可选，支持 DateTimeInterface、时间戳或日期字符串）
     *
     * @throws InvalidArgumentException 计划不可用、目标无效或时间格式错误
     *
     * @return Voucher 创建的凭据
     */
    public function create(ShouldSettlement $settlement, Plan $plan, mixed $scheduledAt = null): Voucher
    {
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
            $payload['scheduled_at'] = $this->parseScheduledAt($scheduledAt);
        }

        $voucher = Voucher::create($payload);

        $this->dispatchAutoRun($voucher);

        return $voucher;
    }

    /**
     * 调度自动执行任务
     */
    protected function dispatchAutoRun(Voucher $voucher): void
    {
        if ($voucher->scheduled_at?->isFuture()) {
            VoucherAutoRunJob::dispatch($voucher)->delay($voucher->scheduled_at);
        } else {
            VoucherAutoRunJob::dispatch($voucher);
        }
    }

    /**
     * 解析计划执行时间
     *
     * @param  DateTimeInterface|int|string  $scheduledAt  执行时间
     *
     * @throws InvalidArgumentException 时间格式错误
     *
     * @return Carbon 解析后的时间
     */
    protected function parseScheduledAt(DateTimeInterface|int|string $scheduledAt): Carbon
    {
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

        return $scheduled;
    }
}
