<?php

namespace App\Enums\Content;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SuggestType: string implements HasColor, HasLabel
{
    case Feature = 'feature';

    case Bug = 'bug';

    case Account = 'account';

    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Feature => '功能建议',
            self::Bug => '问题反馈',
            self::Account => '账号问题',
            self::Other => '其他',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Feature => 'primary',
            self::Bug => 'danger',
            self::Account => 'warning',
            self::Other => 'gray',
        };
    }
}
