<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 订单状态枚举类
 */
enum OrderStatus: string implements HasColor, HasLabel
{
    /**
     * 订单初始化：用户已下单，未付款
     */
    case Pending = 'pending';

    /**
     * 订单取消：用户未支付并取消订单，超时未支付后自动取消订单
     */
    case Canceled = 'canceled';

    /**
     * 已支付：用户付款完成，等待发货
     */
    case Paid = 'paid';

    /**
     * 备货中：打印订单、拣货、打包
     */
    case Preparing = 'preparing';

    /**
     * 部分发货：部分商品已发货
     */
    case PartiallyShipped = 'partially';

    /**
     * 已发货：卖家已发货
     */
    case Delivered = 'delivered';

    /**
     * 已签收：用户已签收
     */
    case Signed = 'signed';

    /**
     * 已完成：用户签收N天后，完成订单，不再做任何操作
     */
    case Completed = 'completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待付款',
            self::Canceled => '已取消',
            self::Paid => '待发货',
            self::Preparing => '备货中',
            self::PartiallyShipped => '部分发货',
            self::Delivered => '已发货',
            self::Signed => '已签收',
            self::Completed => '已完成',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Canceled => 'red',
            self::Paid => 'blue',
            self::Preparing => 'sky',
            self::PartiallyShipped => 'cyan',
            self::Delivered => 'indigo',
            self::Signed => 'teal',
            self::Completed => 'emerald',
        };
    }
}
