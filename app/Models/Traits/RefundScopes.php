<?php

namespace App\Models\Traits;

use App\Enums\Mall\RefundStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 售后订单查询作用域特征
 *
 * @property RefundStatus $status
 */
trait RefundScopes
{
    /**
     * 待审核
     */
    #[Scope]
    protected function ofPending(Builder $query): void
    {
        $query->where('status', RefundStatus::Pending);
    }

    /**
     * 处理中（等待退货、退货中、已签收、退款处理中、退款失败）
     */
    #[Scope]
    protected function ofProcessing(Builder $query): void
    {
        $query->whereIn('status', [
            RefundStatus::WaitingReturn,
            RefundStatus::Shipping,
            RefundStatus::Received,
            RefundStatus::Processing,
            RefundStatus::Failed,
        ]);
    }

    /**
     * 已完成
     */
    #[Scope]
    protected function ofCompleted(Builder $query): void
    {
        $query->where('status', RefundStatus::Completed);
    }

    /**
     * 已关闭（审核拒绝、已取消）
     */
    #[Scope]
    protected function ofClosed(Builder $query): void
    {
        $query->whereIn('status', [
            RefundStatus::Rejected,
            RefundStatus::Cancelled,
        ]);
    }
}
