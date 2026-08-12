<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RefundReason: string implements HasColor, HasLabel
{
    /**
     * 不想要了
     */
    case NotWant = 'not_want';

    /**
     * 拍错/多拍
     */
    case WrongOrder = 'wrong_order';

    /**
     * 未收到货
     */
    case NotReceived = 'not_received';

    /**
     * 未按时发货
     */
    case LateDelivery = 'late_delivery';

    /**
     * 质量问题
     */
    case QualityIssue = 'quality';

    /**
     * 商品破损
     */
    case Damaged = 'damaged';

    /**
     * 描述不符
     */
    case NotAsDescribed = 'not_as_described';

    /**
     * 尺寸不合适
     */
    case SizeIssue = 'size';

    /**
     * 发错货
     */
    case WrongItem = 'wrong_item';

    /**
     * 少发/漏发
     */
    case MissingItem = 'missing_item';

    /**
     * 假货
     */
    case Counterfeit = 'counterfeit';

    /**
     * 其他
     */
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::NotWant => '不想要了',
            self::WrongOrder => '拍错/多拍',
            self::NotReceived => '未收到货',
            self::LateDelivery => '未按时发货',
            self::QualityIssue => '质量问题',
            self::Damaged => '商品破损',
            self::NotAsDescribed => '描述不符',
            self::SizeIssue => '尺寸不合适',
            self::WrongItem => '发错货',
            self::MissingItem => '少发/漏发',
            self::Counterfeit => '假货',
            self::Other => '其他',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NotWant => 'gray',
            self::WrongOrder => 'info',
            self::NotReceived => 'danger',
            self::LateDelivery => 'warning',
            self::QualityIssue => 'danger',
            self::Damaged => 'danger',
            self::NotAsDescribed => 'orange',
            self::SizeIssue => 'info',
            self::WrongItem => 'purple',
            self::MissingItem => 'pink',
            self::Counterfeit => 'danger',
            self::Other => 'gray',
        };
    }
}
