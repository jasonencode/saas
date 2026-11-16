<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasLabel;

/**
 * 结算计划触发方式
 */
enum PlanTrigger: string implements HasLabel
{
    /**
     * 仅手工创建凭据
     */
    case Manual = 'manual';

    /**
     * 订单完成自动创建凭据
     */
    case OrderCompleted = 'order_completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => '手工创建',
            self::OrderCompleted => '订单完成',
        };
    }
}
