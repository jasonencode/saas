<?php

namespace App\Enums\Foundation;

use Filament\Support\Contracts\HasLabel;

enum SocialiteProvider: string implements HasLabel
{
    case Alipay = 'Alipay';

    case Douyin = 'Douyin';

    case QQ = 'QQ';

    case Taobao = 'Taobao';

    case WeChat = 'WeChat';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Alipay => '支付宝',
            self::Douyin => '抖音',
            self::QQ => 'QQ',
            self::Taobao => '淘宝',
            self::WeChat => '微信',
        };
    }
}
