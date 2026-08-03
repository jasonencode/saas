<?php

namespace App\Contracts;

/**
 * 用户账户资产类型接口
 */
interface AssetInterface
{
    /**
     * 获取资产字段名
     *
     * @return string 资产字段名
     */
    public function getField(): string;
}
