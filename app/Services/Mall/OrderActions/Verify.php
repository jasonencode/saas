<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderVerified;
use App\Models\Mall\Order;
use App\Services\Mall\OrderActions\Concerns\AssertsOrderState;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 门店自提核销（pickup_pending → verified）
 *
 * 入口：OrderService::verify() / Verify::dispatch()。
 *
 * before：履约方式、核销码非空与状态预校验
 * handle：加锁复核状态与核销码，回写核销时间/核销人并派发 OrderVerified
 * log   ：写日志中心 order.verified
 * after ：空
 */
final class Verify extends AbstractOperation
{
    use AssertsOrderState;

    /**
     * 已去除首尾空白的核销码
     */
    private string $pickupCode;

    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
        string $pickupCode,
    ) {
        $this->pickupCode = trim($pickupCode);
    }

    /**
     * 前置：仅门店自提订单、核销码非空且状态允许
     */
    public function before(): void
    {
        if ($this->order->fulfillment_type !== FulfillmentType::Pickup) {
            throw new RuntimeException('仅门店自提订单支持核销');
        }

        if ($this->pickupCode === '') {
            throw new RuntimeException('核销码不能为空');
        }

        $this->assertCan($this->order, OrderStatus::Verified);
    }

    /**
     * 核销落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 加行锁后重读状态并校验核销码，避免并发重复核销
            $this->assertCanWithLock($this->order, OrderStatus::Verified);

            if ($this->order->pickup_code !== $this->pickupCode) {
                throw new RuntimeException('核销码不正确');
            }

            $this->order->status = OrderStatus::Verified;
            $this->order->verified_at = now();
            $this->order->verified_by = $this->operator->getKey();
            $this->order->save();

            OrderVerified::dispatch($this->order, $this->operator);

            return true;
        });
    }

    /**
     * 记录核销日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.verified', '自提核销', $this->operator, $this->order, null, [
            'pickup_code' => $this->pickupCode,
            'verified_at' => $this->order->verified_at?->toDateTimeString(),
        ]);
    }
}
