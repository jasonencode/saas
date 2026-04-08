<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentGateway: string implements HasColor, HasLabel
{
    case Wechat = 'wechat';

    case Alipay = 'alipay';

    case Balance = 'balance';

    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Wechat => '微信支付',
            self::Alipay => '支付宝',
            self::Balance => '余额支付',
            self::Manual => '线下支付',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Wechat => 'success',
            self::Alipay => 'info',
            self::Balance => 'warning',
            self::Manual => 'indigo',
        };
    }
}
