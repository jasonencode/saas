<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 商品履约方式
 *
 * 决定商品下单后的交付链路：是否需要物流发货、是否需要计算运费、
 * 订单状态机走哪条分支、退款是否涉及物流退货。
 */
enum FulfillmentType: string implements HasColor, HasLabel
{
    /**
     * 快递邮寄：走运费模板计算，需物流发货
     */
    case Mail = 'mail';

    /**
     * 门店自提：免运费，需核销
     */
    case Pickup = 'pickup';

    /**
     * 虚拟商品：免运费，无需物流，库存可不扣减
     */
    case Virtual = 'virtual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mail => '快递邮寄',
            self::Pickup => '门店自提',
            self::Virtual => '虚拟商品',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Mail => 'info',
            self::Pickup => 'warning',
            self::Virtual => 'success',
        };
    }
}
