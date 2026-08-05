<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseCollection;

/**
 * 发票集合
 *
 * 分页响应，list 为发票精简字段，page 为分页元数据。
 */
class InvoiceCollection extends BaseCollection
{
    public $collects = InvoiceResource::class;
}
