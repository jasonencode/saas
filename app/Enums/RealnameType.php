<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RealnameType: string implements HasLabel
{
    case Personal = 'personal';

    case Enterprise = 'enterprise';

    public function getLabel(): string
    {
        return match ($this) {
            self::Personal => '个人认证',
            self::Enterprise => '企业认证',
        };
    }
}
