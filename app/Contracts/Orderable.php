<?php

namespace App\Contracts;

/**
 * 可订购模型契约
 *
 * 任何希望被订单系统购买的模型（商品规格、虚拟权益、充值套餐、预约项目等）
 * 都应实现此接口。订单服务通过契约统一处理校验、定价、库存扣减与回退。
 */
interface Orderable
{
    /**
     * 获取可订购项的租户 ID
     */
    public function getTenantId(): int;

    /**
     * 获取可订购项展示名称（下单快照用）
     */
    public function getOrderableName(): string;

    /**
     * 获取单价（保留两位小数的字符串）
     */
    public function getOrderablePrice(): string;

    /**
     * 下单前校验
     *
     * @param  int  $qty  购买数量
     *
     * @return string|null 失败理由，null 表示校验通过
     */
    public function checkOrderable(int $qty = 1): ?string;

    /**
     * 扣减库存（下单或发货时调用）
     *
     * 虚拟商品等无库存类型可实现为 no-op。
     *
     * @param  int  $qty  扣减数量
     */
    public function deductStock(int $qty): void;

    /**
     * 回退库存（取消/退款时调用）
     *
     * 虚拟商品等无库存类型可实现为 no-op。
     *
     * @param  int  $qty  回退数量
     */
    public function restoreStock(int $qty): void;
}
