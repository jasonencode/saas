<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WithdrawGateway: string implements HasColor, HasLabel
{
    case Wechat = 'wechat';

    case Alipay = 'alipay';

    case Bank = 'bank';

    public function getLabel(): string
    {
        return match ($this) {
            self::Wechat => '微信提现',
            self::Alipay => '支付宝提现',
            self::Bank => '银行卡提现',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Wechat => 'success',
            self::Alipay => 'info',
            self::Bank => 'warning',
        };
    }
}
