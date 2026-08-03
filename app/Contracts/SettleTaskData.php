<?php

namespace App\Contracts;

use App\Models\Finance\Voucher;

/**
 * 结算任务数据
 */
class SettleTaskData
{
    public array $parameters;

    /**
     * 初始化结算任务数据
     *
     * @param  Voucher  $voucher  结算凭据
     * @param  array  $parameters  所有参数，可以传递给下一步用
     */
    public function __construct(public Voucher $voucher, array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * 合并参数
     *
     * @param  array  $parameter  需要合并的参数
     */
    public function addParameter(array $parameter): void
    {
        $this->parameters = array_merge($this->parameters, $parameter);
    }

    /**
     * 获取参数列表
     *
     * @return array 参数列表
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
