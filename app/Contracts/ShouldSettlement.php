<?php

namespace App\Contracts;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 可结算模型接口
 *
 * 可结算模型是指能够作为结算凭据（Voucher）目标的多态关联模型，
 * 必须关联一个用户，并提供结算展示标题。
 *
 * @property User $user
 *
 * @mixin Model
 */
interface ShouldSettlement
{
    /**
     * 关联用户
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo;

    /**
     * 结算展示标题
     *
     * 用于在结算凭据列表、详情页中展示结算目标的可读名称。
     */
    public function getSettlementTitleAttribute(): string;
}
