<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderCompleted;
use App\Events\Mall\OrderPaid;
use App\Models\Mall\Order;
use App\Notifications\NewOrderToTenant;
use App\Services\Mall\OrderActions\Concerns\AssertsOrderState;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

/**
 * 订单支付成功（pending → paid，按履约方式分流）
 *
 * 入口：OrderService::pay() / Pay::dispatch()。
 *
 * before：状态预校验（handle 内加锁复核）
 * handle：状态落库、支付时间、自提核销码、租户通知与支付/完成事件
 * log   ：写日志中心 order.paid
 * after ：空
 */
final class Pay extends AbstractOperation
{
    use AssertsOrderState;

    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $user,
    ) {}

    /**
     * 前置：只有待付款订单可以支付
     */
    public function before(): void
    {
        $this->assertCan($this->order, OrderStatus::Paid);
    }

    /**
     * 状态落库并派发支付后的副作用
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->assertCanWithLock($this->order, OrderStatus::Paid);

            // 按履约方式自动推进目标状态：mail → 待发货，pickup → 待自提，virtual → 已完成
            $targetStatus = match ($this->order->fulfillment_type) {
                FulfillmentType::Pickup => OrderStatus::PickupPending,
                FulfillmentType::Virtual => OrderStatus::Completed,
                default => OrderStatus::Paid,
            };

            $this->order->status = $targetStatus;
            $this->order->paid_at = now();

            // pickup 订单付款后生成核销码（幂等：已有则复用）
            if ($this->order->fulfillment_type === FulfillmentType::Pickup && !$this->order->pickup_code) {
                $this->order->pickup_code = $this->generatePickupCode();
            }

            $this->order->save();

            $this->order->tenant->notify(new NewOrderToTenant($this->order));

            OrderPaid::dispatch($this->order, $this->user);

            // virtual 订单支付即完成，派发完成事件（下游结算等逻辑复用）
            if ($targetStatus === OrderStatus::Completed) {
                OrderCompleted::dispatch($this->order, $this->user);
            }

            return true;
        });
    }

    /**
     * 记录支付成功日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.paid', '订单支付', $this->user, $this->order, null, [
            'status' => $this->order->status->value,
            'paid_at' => $this->order->paid_at?->toDateTimeString(),
        ]);
    }

    /**
     * 生成自提核销码
     *
     * 格式：PICK-XXXX-XXXX-XXXX，使用大写字母与数字（去除易混淆的 0/O/1/I）。
     *
     * @throws RandomException
     */
    private function generatePickupCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $segment = '';
            for ($j = 0; $j < 4; $j++) {
                $segment .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $segments[] = $segment;
        }

        return implode('-', $segments);
    }
}
