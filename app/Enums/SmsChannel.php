<?php

namespace App\Enums;

use App\Enums\Traits\EnumMethods;
use Filament\Support\Contracts\HasLabel;

enum SmsChannel: string implements HasLabel
{
    use EnumMethods;

    case Login = 'login';

    case Forgot = 'forgot';

    case Register = 'register';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Login => '登录通道',
            self::Forgot => '找回密码',
            self::Register => '用户注册',
        };
    }

    public function getTemplate(): ?string
    {
        return match ($this) {
            self::Login => 'TM_0001',
            self::Forgot => 'TM_0002',
            self::Register => 'TM_0003',
        };
    }
}
