<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseCollection;

/**
 * 可开票订单集合
 *
 * 分页响应，list 为订单精简字段，page 为分页元数据。
 */
class InvoicableOrderCollection extends BaseCollection
{
    public $collects = InvoicableOrderResource::class;
}
