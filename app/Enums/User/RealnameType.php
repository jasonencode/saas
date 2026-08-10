<?php

namespace App\Enums\User;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RealnameType: string implements HasColor, HasLabel
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

    public function getColor(): string
    {
        return match ($this) {
            self::Personal => 'primary',
            self::Enterprise => 'danger',
        };
    }
}
