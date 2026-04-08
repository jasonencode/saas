<?php

namespace App\Contracts;

/**
 * 用户账户资产类型接口
 */
interface AssetInterface
{
    public function getField(): string;
}
