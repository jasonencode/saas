<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\System\EventLogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 订单延时签收（delivered → 延长自动签收时间）
 *
 * 入口：OrderService::delaySign() / DelaySign::dispatch()。
 *
 * before：仅已发货订单可延时，且未超过可延时期限
 * handle：加锁复核后把自动签收时间往后延 7 天
 * log   ：写日志中心 order.sign_delayed（含改前/改后时间）
 * after ：空（自动签收由 app:mall:order-auto-sign 命令按 auto_sign_at 扫描）
 *
 * 可延时期限：当前自动签收时间再往后 7 天内仍可操作，超过后不再允许。
 */
final class DelaySign extends AbstractOperation
{
    /**
     * 每次延长的天数
     */
    private const int EXTEND_DAYS = 7;

    /**
     * 延时前的自动签收时间（handle 写入、log 读取）
     */
    private ?Carbon $previousDeadline = null;

    /**
     * @param  Order  $order  订单
     * @param  Authenticatable  $operator  操作人（用户 / 后台管理员）
     */
    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：状态与可延时期限校验
     */
    public function before(): void
    {
        $this->assertDelayable($this->order);
    }

    /**
     * 延长自动签收时间并落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 加锁复核：自动签收任务与用户延时并发时，避免签收后再延时
            Order::whereKey($this->order->getKey())->lockForUpdate()->first();
            $this->order->refresh();

            $this->assertDelayable($this->order);

            $this->previousDeadline = $this->resolveDeadline($this->order);

            $this->order->auto_sign_at = $this->previousDeadline->copy()->addDays(self::EXTEND_DAYS);
            $this->order->save();

            return true;
        });
    }

    /**
     * 记录延时签收日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.sign_delayed', '延时签收', $this->operator, $this->order, null, [
            'from' => $this->previousDeadline?->toDateTimeString(),
            'to' => $this->order->auto_sign_at?->toDateTimeString(),
        ]);
    }

    /**
     * 状态与期限校验（before 与锁内复核共用）
     */
    private function assertDelayable(Order $order): void
    {
        if ($order->status !== OrderStatus::Delivered) {
            throw new RuntimeException('当前订单状态不可延时签收');
        }

        $deadline = $this->resolveDeadline($order);

        if (now()->greaterThan($deadline->copy()->addDays(self::EXTEND_DAYS))) {
            throw new RuntimeException('订单超出可延时签收期限');
        }
    }

    /**
     * 计算当前自动签收时间
     *
     * auto_sign_at 为空的历史订单按最晚发货时间 + 自动签收天数补算；
     * 仍无法确定时从当前时间起算。
     */
    private function resolveDeadline(Order $order): Carbon
    {
        if ($order->auto_sign_at) {
            return $order->auto_sign_at->copy();
        }

        $deliverAt = $order->shippings()->max('delivery_at');

        return Carbon::parse($deliverAt ?? now())
            ->addDays((int) config('custom.mall.auto_sign_days', 7));
    }
}
