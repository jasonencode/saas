<?php

namespace App\Services\Mall;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefundService implements ServiceInterface
{
    /**
     * 退款完成处理
     *
     * 当支付网关回调退款成功时调用此方法，处理退款后续逻辑：
     * 1. 更新退款单状态
     * 2. 回退库存
     * 3. 记录日志
     *
     * @param  Refund  $refund  退款单
     * @param  bool  $result  退款是否成功
     * @param  string|null  $desc  退款描述/备注
     * @param  array|null  $data  额外数据（如网关返回信息）
     *
     * @throws Throwable 数据库事务异常
     */
    public function refunded(Refund $refund, bool $result, ?string $desc = null, ?array $data = null): void
    {
        DB::transaction(function () use ($refund, $result, $desc, $data) {
            if ($result) {
                $this->handleSuccess($refund, $desc, $data);
            } else {
                $this->handleFailure($refund, $desc, $data);
            }
        });
    }

    /**
     * 处理退款成功
     */
    private function handleSuccess(Refund $refund, ?string $desc, ?array $data): void
    {
        $refund->status = RefundStatus::Completed;
        $refund->refund_at = now();

        // 回退库存
        foreach ($refund->items as $item) {
            $sku = $item->orderItem?->sku;
            if ($sku) {
                $sku->stock += $item->qty;
                $sku->save();
            }
        }

        $refund->save();

        // 记录退款成功日志
        $refund->logs()->create([
            'user_type' => null,
            'user_id' => null,
            'context' => [
                'action' => 'refunded',
                'remark' => $desc ?? '退款成功',
                'data' => $data,
            ],
        ]);
    }

    /**
     * 处理退款失败
     */
    private function handleFailure(Refund $refund, ?string $desc, ?array $data): void
    {
        $refund->status = RefundStatus::Failed;
        $refund->save();

        // 记录退款失败日志
        $refund->logs()->create([
            'user_type' => null,
            'user_id' => null,
            'context' => [
                'action' => 'refund_failed',
                'remark' => $desc ?? '退款失败',
                'data' => $data,
            ],
        ]);
    }
}
