<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\BaseCollection;

/**
 * 提现订单集合
 *
 * 分页响应，list 为订单数据，page 为分页元数据。
 */
class WithdrawOrderCollection extends BaseCollection
{
    public $collects = WithdrawOrderResource::class;
}