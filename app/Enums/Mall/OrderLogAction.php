<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderLogAction: string implements HasColor, HasLabel
{
    /**
     * 订单创建
     */
    case Created = 'created';

    /**
     * 订单取消
     */
    case Canceled = 'canceled';

    /**
     * 订单支付
     */
    case Paid = 'paid';

    /**
     * 开始备货
     */
    case Preparing = 'preparing';

    /**
     * 订单发货
     */
    case Delivered = 'delivered';

    /**
     * 删除发货记录
     */
    case ExpressDeleted = 'express_deleted';

    /**
     * 订单签收
     */
    case Signed = 'signed';

    /**
     * 订单完成
     */
    case Completed = 'completed';

    /**
     * 订单删除
     */
    case Deleted = 'deleted';

    /**
     * 修改收货地址
     */
    case AddressModified = 'address_modified';

    /**
     * 添加商家备注
     */
    case SellerRemarkAdded = 'seller_remark_added';

    public function getLabel(): string
    {
        return match ($this) {
            self::Created => '订单创建',
            self::Canceled => '订单取消',
            self::Paid => '订单支付',
            self::Preparing => '开始备货',
            self::Delivered => '订单发货',
            self::ExpressDeleted => '删除发货',
            self::Signed => '订单签收',
            self::Completed => '订单完成',
            self::Deleted => '订单删除',
            self::AddressModified => '修改地址',
            self::SellerRemarkAdded => '商家备注',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Created => 'gray',
            self::Canceled => 'red',
            self::Paid => 'blue',
            self::Preparing => 'sky',
            self::Delivered => 'indigo',
            self::ExpressDeleted => 'orange',
            self::Signed => 'teal',
            self::Completed => 'emerald',
            self::Deleted => 'red',
            self::AddressModified => 'amber',
            self::SellerRemarkAdded => 'amber',
        };
    }
}
