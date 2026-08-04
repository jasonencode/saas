<?php

namespace App\Contracts;

use App\Models\Mall\OrderItem;
use App\Models\Mall\RefundItem;

/**
 * 可退款模型契约
 *
 * 实现此接口的可订购主体（Orderable）能够参与完整退款流程。
 * 退款服务在退款确认（confirmRefund）时通过契约委托具体类型处理：
 * - 是否需要物流退货（实体商品 vs 虚拟权益）
 * - 退款时的资源回收（库存回退、身份撤销等）
 *
 * 未实现此接口的可订购类型，退款服务将跳过资源回收。
 */
interface Refundable
{
    /**
     * 是否需要买家寄回商品
     *
     * 实体商品返回 true，虚拟权益/充值/预约返回 false。
     */
    public function needsReturn(): bool;

    /**
     * 退款资源回收
     *
     * 在退款完成（confirmRefund）时调用，由具体类型执行：
     * - 实体商品 SKU：回退库存
     * - 虚拟权益 Identity：撤销已授予的身份
     * - 充值套餐：扣减账户余额
     *
     * @param  RefundItem  $refundItem  退款明细（含关联的 OrderItem、订单用户等上下文）
     * @param  int  $qty  退款数量
     */
    public function refund(RefundItem $refundItem, int $qty): void;
}
