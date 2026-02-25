<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FilamentPanelGroup: string implements HasLabel
{
    case Content = 'content';

    case System = 'system';

    case User = 'user';

    case Tenant = 'tenant';

    case Api = 'api';

    case Support = 'support';

    case Account = 'account';

    case Socialite = 'socialite';

    public function getLabel(): string
    {
        return match ($this) {
            self::Content => '内容',
            self::System => '系统',
            self::User => '用户',
            self::Tenant => '租户',
            self::Api => 'API',
            self::Support => '维护',
            self::Account => '账户',
            self::Socialite => '社会化登录',
        };
    }
}