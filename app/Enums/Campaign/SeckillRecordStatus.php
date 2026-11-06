<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * 秒杀记录状态
 */
enum SeckillRecordStatus: string implements HasColor, HasLabel
{
    /**
     * 已占名额，待创建订单
     */
    case Reserved = 'reserved';

    /**
     * 已下单
     */
    case Ordered = 'ordered';

    /**
     * 已取消（取消/超时）
     */
    case Cancelled = 'cancelled';

    /**
     * 已退款（名额已回补）
     */
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reserved => '已占名额',
            self::Ordered => '已下单',
            self::Cancelled => '已取消',
            self::Refunded => '已退款',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reserved => 'warning',
            self::Ordered => 'success',
            self::Cancelled => 'gray',
            self::Refunded => 'danger',
        };
    }

    /**
     * 是否计入限购数量
     */
    public function countsTowardLimit(): bool
    {
        return in_array($this, [self::Reserved, self::Ordered], true);
    }

    /**
     * 是否已结束（不可再次变更）
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Cancelled, self::Refunded], true);
    }
}
