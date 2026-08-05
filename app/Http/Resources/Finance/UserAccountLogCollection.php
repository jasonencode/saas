<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\BaseCollection;

/**
 * 用户账户变动日志集合
 *
 * 分页响应，list 为日志精简字段，page 为分页元数据。
 */
class UserAccountLogCollection extends BaseCollection
{
    public $collects = UserAccountLogResource::class;
}
