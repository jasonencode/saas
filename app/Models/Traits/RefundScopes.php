<?php

namespace App\Models\Traits;

use App\Enums\RefundStatus;
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
     * 待处理作用域
     */
    #[Scope]
    protected function ofPending(Builder $query): void
    {
        $query->whereIn('status', [
            RefundStatus::Pending,
            RefundStatus::Processing,
        ]);
    }

    /**
     * 处理中作用域
     */
    #[Scope]
    protected function ofProcessing(Builder $query): void
    {
        $query->where('status', RefundStatus::Processing);
    }

    /**
     * 已完成作用域（状态：Completed）
     */
    #[Scope]
    protected function ofCompleted(Builder $query): void
    {
        $query->where('status', RefundStatus::Completed);
    }

    /**
     * 已拒绝作用域（状态：Rejected）
     */
    #[Scope]
    protected function ofRejected(Builder $query): void
    {
        $query->where('status', RefundStatus::Rejected);
    }

    /**
     * 已取消作用域（状态：Cancelled）
     */
    #[Scope]
    protected function ofCancelled(Builder $query): void
    {
        $query->where('status', RefundStatus::Cancelled);
    }

    /**
     * 失败作用域（状态：Failed）
     */
    #[Scope]
    protected function ofFailed(Builder $query): void
    {
        $query->where('status', RefundStatus::Failed);
    }
}
