<?php

namespace App\Enums\User;

use Filament\Support\Contracts\HasLabel;

enum SmsChannel: string implements HasLabel
{
    case Login = 'login';

    case Register = 'register';

    case Forgot = 'forgot';

    public function getLabel(): string
    {
        return match ($this) {
            self::Login => '登录',
            self::Register => '注册',
            self::Forgot => '忘记密码',
        };
    }

    public function getTemplate(): string
    {
        return match ($this) {
            self::Login => '登录验证码',
            self::Register => '注册验证码',
            self::Forgot => '忘记密码验证码',
        };
    }
}
