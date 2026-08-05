<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseCollection;

/**
 * 发票申请集合
 *
 * 分页响应，list 为发票申请精简字段，page 为分页元数据。
 */
class InvoiceApplicationCollection extends BaseCollection
{
    public $collects = InvoiceApplicationResource::class;
}
